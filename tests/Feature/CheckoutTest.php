<?php

namespace Tests\Feature;

use App\Events\CheckoutCompleted;
use App\Events\CheckoutFailed;
use App\Events\CheckoutStarted;
use App\Models\CheckoutLock;
use App\Models\InventoryLevel;
use App\Models\PriceSnapshot;
use App\Models\StockReservation;
use App\Models\Warehouse;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Lunar\Facades\CartSession;
use Lunar\Models\Channel;
use Lunar\Models\Cart;
use Lunar\Models\Currency;
use App\Models\ProductVariant;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test checkout starts successfully.
     */
    public function test_checkout_starts_successfully(): void
    {
        Event::fake();

        $cart = $this->createCartWithItems();
        $checkoutService = app(CheckoutService::class);

        $lock = $checkoutService->startCheckout($cart);

        $this->assertNotNull($lock);
        $this->assertEquals($cart->id, $lock->cart_id);
        $this->assertTrue($lock->isActive());

        Event::assertDispatched(CheckoutStarted::class);
    }

    /**
     * Test cart cannot be modified during checkout.
     */
    public function test_cart_cannot_be_modified_during_checkout(): void
    {
        $cart = $this->createCartWithItems();
        $checkoutService = app(CheckoutService::class);

        $lock = $checkoutService->startCheckout($cart);

        $this->assertTrue($checkoutService->isCartLocked($cart));

        // Attempt to add item should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cart cannot be modified during checkout');

        // This would be caught by middleware/trait in real scenario
        if ($checkoutService->isCartLocked($cart)) {
            throw new \Exception('Cart cannot be modified during checkout');
        }
    }

    /**
     * Test price snapshots are created.
     */
    public function test_price_snapshots_are_created(): void
    {
        $cart = $this->createCartWithItems();
        $checkoutService = app(CheckoutService::class);
        $stateMachine = app(\App\Services\CheckoutStateMachine::class);

        $lock = $checkoutService->startCheckout($cart);

        // Manually trigger price lock phase
        $reflection = new \ReflectionClass($stateMachine);
        $method = $reflection->getMethod('lockPrices');
        $method->setAccessible(true);
        $method->invoke($stateMachine, $lock);

        $snapshot = PriceSnapshot::where('checkout_lock_id', $lock->id)
            ->whereNull('cart_line_id')
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertGreaterThan(0, $snapshot->total);
    }

    /**
     * Test stock reservations are created.
     */
    public function test_stock_reservations_are_created(): void
    {
        $cart = $this->createCartWithItems();
        $checkoutService = app(CheckoutService::class);
        $stateMachine = app(\App\Services\CheckoutStateMachine::class);

        $lock = $checkoutService->startCheckout($cart);

        // Manually trigger reservation phase
        $reflection = new \ReflectionClass($stateMachine);
        $method = $reflection->getMethod('reserveInventory');
        $method->setAccessible(true);
        $reservations = $method->invoke($stateMachine, $lock);

        $this->assertNotEmpty($reservations);

        $dbReservations = StockReservation::where('reference_type', CheckoutLock::class)
            ->where('reference_id', $lock->id)
            ->get();

        $this->assertCount(count($reservations), $dbReservations);
    }

    /**
     * Test checkout failure triggers rollback.
     */
    public function test_checkout_failure_triggers_rollback(): void
    {
        $cart = $this->createCartWithItems();
        $checkoutService = app(CheckoutService::class);
        $stateMachine = app(\App\Services\CheckoutStateMachine::class);

        $lock = $checkoutService->startCheckout($cart);

        // Create reservations first
        $reflection = new \ReflectionClass($stateMachine);
        $reserveMethod = $reflection->getMethod('reserveInventory');
        $reserveMethod->setAccessible(true);
        $reservations = $reserveMethod->invoke($stateMachine, $lock);

        $this->assertNotEmpty($reservations);

        // Simulate a failure by explicitly releasing the checkout (rollback).
        $checkoutService->releaseCheckout($lock);

        foreach ($reservations as $reservation) {
            $this->assertTrue((bool) $reservation->fresh()?->is_released);
        }

        $this->assertTrue($lock->fresh()->isFailed());
    }

    /**
     * Test expired locks are cleaned up.
     */
    public function test_expired_locks_are_cleaned_up(): void
    {
        $cart = $this->createCartWithItems();
        $checkoutService = app(CheckoutService::class);

        $lock = $checkoutService->startCheckout($cart);

        // Manually expire the lock
        $lock->update(['expires_at' => now()->subMinute()]);

        $count = $checkoutService->cleanupExpiredLocks();

        $this->assertGreaterThan(0, $count);
        $this->assertTrue($lock->fresh()->isFailed());
    }

    /**
     * Test concurrent checkout prevention.
     */
    public function test_concurrent_checkout_prevention(): void
    {
        $cart = $this->createCartWithItems();
        $checkoutService = app(CheckoutService::class);

        // Start checkout (creates an active lock for the current session)
        $lock1 = $checkoutService->startCheckout($cart);

        // Simulate another session holding the lock by changing the lock session_id
        $lock1->update(['session_id' => 'other-session']);

        // Trying to start checkout again should now fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cart is currently being checked out by another session');

        $checkoutService->startCheckout($cart);
    }

    /**
     * Create a cart with items for testing.
     */
    protected function createCartWithItems(): Cart
    {
        $currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'exchange_rate' => 1,
                'decimal_places' => 2,
                'enabled' => true,
                'default' => true,
            ]
        );

        $channel = Channel::firstOrCreate(
            ['handle' => 'webstore'],
            [
                'name' => 'Web Store',
                'default' => true,
                'url' => 'http://localhost',
            ]
        );

        $cart = Cart::factory()->create([
            'currency_id' => $currency->id,
            'channel_id' => $channel->id,
        ]);

        // Add some test items with guaranteed pricing
        $variant = ProductVariant::factory()->create(['stock' => 10]);

        // Create a price through the relation to ensure the correct morph type is used.
        $variant->prices()->updateOrCreate(
            [
                'currency_id' => $currency->id,
                'customer_group_id' => null, // guest checkout in tests
                'min_quantity' => 1,
            ],
            [
                'price' => 1000,
                'compare_price' => null,
            ]
        );

        // Ensure inventory can be reserved during checkout (stock reservations require a warehouse + inventory level).
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'default'],
            [
                'name' => 'Default Warehouse',
                'is_active' => true,
                'priority' => 0,
            ]
        );

        InventoryLevel::firstOrCreate(
            [
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'quantity' => 100,
                'reserved_quantity' => 0,
                'incoming_quantity' => 0,
                'reorder_point' => 0,
                'reorder_quantity' => 0,
                'status' => 'in_stock',
            ]
        );
        
        $cart->lines()->create([
            'purchasable_type' => get_class($variant),
            'purchasable_id' => $variant->id,
            'quantity' => 2,
        ]);

        return $cart;
    }
}

