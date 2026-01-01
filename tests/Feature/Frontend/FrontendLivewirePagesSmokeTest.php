<?php

namespace Tests\Feature\Frontend;

use App\Lunar\Customers\CustomerHelper;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Brand;
use Tests\TestCase;

class FrontendLivewirePagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_brands_index_page_renders(): void
    {
        $this->get(route('frontend.brands.index'))
            ->assertOk()
            ->assertSee('Brand Directory');
    }

    public function test_brand_show_page_renders(): void
    {
        $brand = Brand::factory()->create();

        $this->get(route('frontend.brands.show', ['slug' => (string) $brand->getKey()]))
            ->assertOk()
            ->assertSee($brand->name);
    }

    public function test_categories_index_page_renders(): void
    {
        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Categories');
    }

    public function test_category_show_page_renders(): void
    {
        $category = Category::factory()->create([
            'name' => ['en' => 'Electronics'],
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $this->get(route('categories.show', ['path' => $category->slug]))
            ->assertOk()
            ->assertSee('Electronics');
    }

    public function test_bundles_index_page_renders(): void
    {
        $this->get(route('frontend.bundles.index'))
            ->assertOk()
            ->assertSee(__('frontend.bundles.title'));
    }

    public function test_bundle_show_page_renders(): void
    {
        $bundle = Bundle::factory()->create([
            'is_active' => true,
        ]);

        $this->get(route('frontend.bundles.show', ['bundle' => $bundle->slug]))
            ->assertOk()
            ->assertSee($bundle->name);
    }

    public function test_search_index_page_renders_without_query(): void
    {
        $this->get(route('frontend.search.index'))
            ->assertOk()
            ->assertSee(__('frontend.search.results_heading'));
    }

    public function test_cart_index_page_renders(): void
    {
        $this->get(route('frontend.cart.index'))
            ->assertOk()
            ->assertSee(__('frontend.cart.title'));
    }

    public function test_checkout_redirects_to_cart_when_no_cart(): void
    {
        $this->get(route('frontend.checkout.index'))
            ->assertRedirect(route('frontend.cart.index'));
    }

    public function test_product_reviews_index_page_renders(): void
    {
        $product = Product::factory()->active()->create();

        $this->get(route('frontend.reviews.index', ['product' => $product->getKey()]))
            ->assertOk()
            ->assertSee('Reviews for');
    }

    public function test_review_guidelines_page_renders(): void
    {
        $product = Product::factory()->active()->create();

        $this->get(route('frontend.reviews.guidelines', ['product' => $product->getKey()]))
            ->assertOk()
            ->assertSee('Review Guidelines');
    }

    public function test_product_questions_index_page_renders(): void
    {
        $product = Product::factory()->active()->create();

        $this->get(route('frontend.products.questions.index', ['product' => $product->getKey()]))
            ->assertOk()
            ->assertSee('Questions & Answers');
    }

    public function test_addresses_page_requires_authentication(): void
    {
        $this->get(route('frontend.addresses.index'))
            ->assertRedirect(route('login'));
    }

    public function test_addresses_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('frontend.addresses.index'))
            ->assertOk()
            ->assertSee(__('frontend.addresses.title'));
    }

    public function test_downloads_page_requires_authentication(): void
    {
        $this->get(route('frontend.downloads.index'))
            ->assertRedirect(route('login'));
    }

    public function test_downloads_page_renders_for_authenticated_user_with_customer(): void
    {
        $user = User::factory()->create();
        $customer = CustomerHelper::getOrCreateCustomerForUser($user);

        $this->assertNotNull($customer->getKey());

        $this->actingAs($user)
            ->get(route('frontend.downloads.index'))
            ->assertOk()
            ->assertSee(__('frontend.downloads.title'));
    }

    public function test_stock_notification_subscription_sets_customer_id_for_authenticated_user(): void
    {
        // Create a "decoy" customer first so customer IDs won't accidentally match user IDs.
        \Lunar\Models\Customer::factory()->create();

        $user = User::factory()->create();
        $customer = CustomerHelper::getOrCreateCustomerForUser($user);
        $this->assertNotSame($user->getKey(), $customer->getKey(), 'Expected user ID and customer ID to differ for this test.');

        $variant = ProductVariant::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('frontend.stock-notifications.subscribe', ['variant' => $variant->getKey()]), [
                'email' => 'test@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $notificationId = $response->json('notification.id');
        $this->assertNotNull($notificationId);

        $this->assertDatabaseHas('stock_notifications', [
            'id' => $notificationId,
            'customer_id' => $customer->getKey(),
            'email' => 'test@example.com',
            'product_variant_id' => $variant->getKey(),
        ]);
    }
}

