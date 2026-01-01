<?php

namespace Tests\Feature\Frontend;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Lunar\Facades\CartSession;
use Tests\TestCase;

class CartActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_add_update_remove_and_clear_work(): void
    {
        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->inStock(50)->create([
            'product_id' => $product->getKey(),
        ]);

        // Add
        $this->postJson(route('frontend.cart.add'), [
            'variant_id' => $variant->getKey(),
            'quantity' => 2,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('cart.item_count', 2);

        $cart = CartSession::current();
        $this->assertNotNull($cart);
        $cart->load('lines');
        $this->assertCount(1, $cart->lines);
        $lineId = $cart->lines->first()->getKey();

        // Update (quantity 3)
        $this->putJson(route('frontend.cart.update', ['lineId' => $lineId]), [
            'quantity' => 3,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('cart.item_count', 3);

        // Remove
        $this->deleteJson(route('frontend.cart.remove', ['lineId' => $lineId]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('cart.item_count', 0);

        // Add again, then clear
        $this->postJson(route('frontend.cart.add'), [
            'variant_id' => $variant->getKey(),
            'quantity' => 1,
        ])->assertOk();

        $this->deleteJson(route('frontend.cart.clear'))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('cart.item_count', 0);
    }

    public function test_cart_discount_apply_and_remove_work(): void
    {
        Queue::fake();

        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->inStock(50)->create([
            'product_id' => $product->getKey(),
        ]);

        // Create cart
        $this->postJson(route('frontend.cart.add'), [
            'variant_id' => $variant->getKey(),
            'quantity' => 1,
        ])->assertOk();

        // Invalid coupon
        $this->postJson(route('frontend.cart.discount.apply'), [
            'coupon_code' => 'INVALID-COUPON',
        ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // Valid coupon (use the builder so the discount `type` is a real discount type class)
        DiscountService::percentageDiscount('10% Off', 'test-10-off')
            ->percentage(10)
            ->couponCode('TEST10')
            ->startsAt(now()->subMinute())
            ->endsAt(now()->addDay())
            ->create();

        $this->postJson(route('frontend.cart.discount.apply'), [
            'coupon_code' => 'TEST10',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $cart = CartSession::current();
        $this->assertNotNull($cart);
        $this->assertSame('TEST10', $cart->fresh()->coupon_code);

        $this->postJson(route('frontend.cart.discount.remove'))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNull($cart->fresh()->coupon_code);
    }
}

