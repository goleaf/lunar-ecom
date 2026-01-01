<?php

namespace Tests\Feature\Frontend;

use App\Lunar\Customers\CustomerHelper;
use App\Models\Collection;
use App\Models\ComingSoonNotification;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Address;
use Lunar\Models\Order;
use Lunar\Models\Url;
use Tests\TestCase;

class FrontendAdditionalLivewirePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_show_page_renders_for_canonical_slug(): void
    {
        app()->setLocale('en');

        $collection = Collection::factory()->create();
        $collectionName = $collection->translateAttribute('name');

        $url = Url::whereIn('element_type', [Collection::morphName(), Collection::class])
            ->where('element_id', $collection->getKey())
            ->firstOrFail();

        $this->withHeader('Accept-Language', 'en')
            ->get(route('frontend.collections.show', ['slug' => $url->slug]))
            ->assertOk()
            ->assertSee($collectionName);
    }

    public function test_collection_show_page_redirects_numeric_id_to_canonical_slug(): void
    {
        $collection = Collection::factory()->create();

        $url = Url::whereIn('element_type', [Collection::morphName(), Collection::class])
            ->where('element_id', $collection->getKey())
            ->firstOrFail();

        $this->get(route('frontend.collections.show', ['slug' => (string) $collection->getKey()]))
            ->assertRedirect(route('frontend.collections.show', ['slug' => $url->slug]));
    }

    public function test_address_create_page_requires_authentication(): void
    {
        $this->get(route('frontend.addresses.create'))
            ->assertRedirect(route('login'));
    }

    public function test_address_create_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('frontend.addresses.create'))
            ->assertOk()
            ->assertSee(__('frontend.addresses.create_title'));
    }

    public function test_address_edit_page_requires_authentication(): void
    {
        $address = Address::factory()->create();

        $this->get(route('frontend.addresses.edit', $address))
            ->assertRedirect(route('login'));
    }

    public function test_address_edit_page_renders_for_owner(): void
    {
        $user = User::factory()->create();
        $customer = CustomerHelper::getOrCreateCustomerForUser($user);
        $address = Address::factory()->create([
            'customer_id' => $customer->getKey(),
        ]);

        $this->actingAs($user)
            ->get(route('frontend.addresses.edit', $address))
            ->assertOk()
            ->assertSee(__('frontend.addresses.edit_title'));
    }

    public function test_address_edit_page_is_forbidden_for_other_user(): void
    {
        $owner = User::factory()->create();
        $ownerCustomer = CustomerHelper::getOrCreateCustomerForUser($owner);
        $address = Address::factory()->create([
            'customer_id' => $ownerCustomer->getKey(),
        ]);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('frontend.addresses.edit', $address))
            ->assertForbidden();
    }

    public function test_checkout_confirmation_page_is_forbidden_for_guest(): void
    {
        $order = Order::factory()->create();

        $this->get(route('frontend.checkout.confirmation', $order))
            ->assertForbidden();
    }

    public function test_checkout_confirmation_page_renders_for_order_owner(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'paid',
            'placed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('frontend.checkout.confirmation', $order))
            ->assertOk()
            ->assertSee(__('frontend.checkout.order_confirmed'))
            ->assertSee($order->reference);
    }

    public function test_stock_notification_unsubscribe_page_renders_success_for_valid_token(): void
    {
        $variant = ProductVariant::factory()->create();

        $notification = StockNotification::create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->getKey(),
            'customer_id' => null,
            'email' => 'test@example.com',
            'status' => 'pending',
            'notify_on_backorder' => false,
            'min_quantity' => null,
            'token' => 'test-unsubscribe-token',
        ]);

        $this->get(route('frontend.stock-notifications.unsubscribe', ['token' => $notification->token]))
            ->assertOk()
            ->assertSee(__('frontend.stock_notifications.unsubscribe_success_title'));
    }

    public function test_stock_notification_unsubscribe_page_renders_invalid_for_unknown_token(): void
    {
        $this->get(route('frontend.stock-notifications.unsubscribe', ['token' => 'does-not-exist']))
            ->assertOk()
            ->assertSee(__('frontend.stock_notifications.unsubscribe_invalid_title'));
    }

    public function test_coming_soon_unsubscribe_page_renders_and_deletes_subscription(): void
    {
        app()->setLocale('en');

        $product = Product::factory()->active()->create();
        $productName = $product->translateAttribute('name');

        $notification = ComingSoonNotification::create([
            'product_id' => $product->getKey(),
            'email' => 'coming-soon@example.com',
            'customer_id' => null,
            'token' => 'coming-soon-token',
            'notified' => false,
        ]);

        $this->withHeader('Accept-Language', 'en')
            ->get(route('frontend.coming-soon.unsubscribe', ['token' => $notification->token]))
            ->assertOk()
            ->assertSee('You are unsubscribed')
            ->assertSee($productName);

        $this->assertDatabaseMissing('coming_soon_notifications', [
            'id' => $notification->getKey(),
        ]);
    }
}

