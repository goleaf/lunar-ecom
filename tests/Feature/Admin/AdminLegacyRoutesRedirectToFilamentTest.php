<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\BundleResource;
use App\Filament\Resources\CheckoutLockResource;
use App\Filament\Resources\CollectionResource;
use App\Filament\Resources\CustomizationTemplateResource;
use App\Filament\Resources\InventoryLevelResource;
use App\Filament\Resources\PriceMatrixResource;
use App\Filament\Resources\PriceHistoryResource;
use App\Filament\Resources\ProductBadgeResource;
use App\Filament\Resources\ProductBadgeRuleResource;
use App\Filament\Resources\ProductImportResource;
use App\Filament\Resources\ProductResource as FilamentProductResource;
use App\Filament\Resources\ProductAvailabilityResource;
use App\Filament\Resources\ProductQuestionResource;
use App\Filament\Resources\ProductScheduleResource;
use App\Filament\Resources\SmartCollectionRuleResource;
use App\Models\Bundle;
use App\Models\CheckoutLock;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductBadge;
use App\Models\ProductBadgeRule;
use App\Models\ProductQuestion;
use App\Models\ProductImport;
use App\Models\ProductSchedule;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Lunar\Models\Channel;
use Lunar\Models\Cart;
use Lunar\Models\Currency;
use Tests\TestCase;

class AdminLegacyRoutesRedirectToFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_moderation_page_redirects_to_filament_reviews_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $this->get(route('admin.reviews.moderation'))
            ->assertRedirect(route('filament.admin.resources.reviews.index'));
    }

    public function test_stock_dashboard_redirects_to_filament_inventory_levels_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $slug = InventoryLevelResource::getSlug();

        $this->get(route('admin.stock.index'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));
    }

    public function test_stock_variant_page_redirects_to_filament_inventory_levels_with_search(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $variant = ProductVariant::factory()->create();

        $slug = InventoryLevelResource::getSlug();

        $this->get(route('admin.stock.show', ['variant' => $variant->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index", [
                'tableSearch' => (string) $variant->sku,
            ]));
    }

    public function test_bundle_pages_redirect_to_filament_bundle_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $bundle = Bundle::factory()->create();

        $slug = BundleResource::getSlug();

        $this->get(route('admin.bundles.index'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));

        $this->get(route('admin.bundles.create'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.create"));

        $this->get(route('admin.bundles.edit', ['bundle' => $bundle->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.edit", ['record' => $bundle->getKey()]));
    }

    public function test_product_import_pages_redirect_to_filament_product_import_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $slug = ProductImportResource::getSlug();

        $this->get(route('admin.products.import-export'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));

        $this->get(route('admin.products.import.index'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));

        $import = ProductImport::factory()->create();

        $this->get(route('admin.products.import.report', ['id' => $import->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.view", ['record' => $import->getKey()]));
    }

    public function test_checkout_locks_pages_redirect_to_filament_checkout_lock_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'exchange_rate' => 1,
                'decimal_places' => 2,
                'default' => true,
                'enabled' => true,
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

        $lock = CheckoutLock::create([
            'cart_id' => $cart->id,
            'session_id' => 'sess-' . Str::lower(Str::random(12)),
            'state' => CheckoutLock::STATE_PENDING,
            'expires_at' => now()->addMinutes(30),
        ]);

        $slug = CheckoutLockResource::getSlug();

        $this->get(route('admin.checkout-locks.index'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));

        $this->get(route('admin.checkout-locks.show', ['checkoutLock' => $lock->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.view", ['record' => $lock->getKey()]));
    }

    public function test_price_matrix_product_page_redirects_to_filament_price_matrix_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $slug = PriceMatrixResource::getSlug();

        $this->get(route('admin.products.pricing.matrices.index', ['product' => $product->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index", ['product_id' => $product->getKey()]));
    }

    public function test_pricing_import_page_redirects_to_filament_price_matrix_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $slug = PriceMatrixResource::getSlug();

        $this->get(route('admin.products.pricing.import.index', ['product' => $product->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index", ['product_id' => $product->getKey()]));
    }

    public function test_pricing_history_product_page_redirects_to_filament_price_history_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $slug = PriceHistoryResource::getSlug();

        $this->get(route('admin.products.pricing.history', ['product' => $product->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));
    }

    public function test_collection_manage_page_redirects_to_filament_collection_resource_edit(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $collection = Collection::factory()->create();

        $slug = CollectionResource::getSlug();

        $this->get(route('admin.collections.manage', ['collection' => $collection->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.edit", ['record' => $collection->getKey()]));
    }

    public function test_collection_smart_rules_page_redirects_to_filament_smart_collection_rule_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $collection = Collection::factory()->create();

        $slug = SmartCollectionRuleResource::getSlug();

        $this->get(route('admin.collections.smart-rules', ['collection' => $collection->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index", [
                'tableFilters' => [
                    'collection_id' => [
                        'value' => $collection->getKey(),
                    ],
                ],
            ]));
    }

    public function test_product_badges_pages_redirect_to_filament_product_badge_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $badge = ProductBadge::create([
            'name' => 'Badge ' . Str::upper(Str::random(6)),
        ]);

        $slug = ProductBadgeResource::getSlug();

        $this->get(route('admin.badges.index'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));

        $this->get(route('admin.badges.create'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.create"));

        $this->get(route('admin.badges.show', ['badge' => $badge->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.edit", ['record' => $badge->getKey()]));

        $this->get(route('admin.badges.edit', ['badge' => $badge->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.edit", ['record' => $badge->getKey()]));
    }

    public function test_product_badge_assignments_page_redirects_to_filament_product_edit(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $slug = FilamentProductResource::getSlug();

        $this->get(route('admin.badges.products.index', ['product' => $product->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.edit", ['record' => $product->getKey()]));
    }

    public function test_admin_product_customizations_pages_redirect_to_filament_product_edit(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $slug = FilamentProductResource::getSlug();

        $this->get(route('admin.products.customizations.index', ['product' => $product->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.edit", ['record' => $product->getKey()]));

        $this->get(route('admin.products.customizations.examples', ['product' => $product->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.edit", ['record' => $product->getKey()]));
    }

    public function test_admin_customization_templates_page_redirects_to_filament_customization_template_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $slug = CustomizationTemplateResource::getSlug();

        $this->get(route('admin.customizations.templates'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));
    }

    public function test_product_badge_rules_pages_redirect_to_filament_product_badge_rule_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $badge = ProductBadge::create([
            'name' => 'Badge ' . Str::upper(Str::random(6)),
        ]);

        $rule = ProductBadgeRule::create([
            'badge_id' => $badge->getKey(),
            'condition_type' => 'automatic',
            'conditions' => [],
        ]);

        $slug = ProductBadgeRuleResource::getSlug();

        $this->get(route('admin.badges.rules.index'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));

        $this->get(route('admin.badges.rules.create'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.create"));

        $this->get(route('admin.badges.rules.show', ['rule' => $rule->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.edit", ['record' => $rule->getKey()]));

        $this->get(route('admin.badges.rules.edit', ['rule' => $rule->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.edit", ['record' => $rule->getKey()]));
    }

    public function test_product_schedules_pages_redirect_to_filament_product_schedule_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $slug = ProductScheduleResource::getSlug();

        $this->get(route('admin.products.schedules.index', ['product' => $product->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index", ['product_id' => $product->getKey()]));

        $this->get(route('admin.schedules.history'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));
    }

    public function test_schedule_calendar_page_redirects_to_filament_product_schedule_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $slug = ProductScheduleResource::getSlug();

        $this->get(route('admin.schedules.calendar'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));
    }

    public function test_schedule_calendar_feed_returns_filament_urls(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $schedule = ProductSchedule::create([
            'product_id' => $product->getKey(),
            'type' => 'publish',
            'scheduled_at' => now()->addDay(),
            'expires_at' => now()->addDays(2),
            'is_active' => true,
        ]);

        $slug = ProductScheduleResource::getSlug();

        $payload = $this->getJson(route('admin.schedules.calendar.schedules', [
            'start' => now()->subDay()->toDateString(),
            'end' => now()->addDays(7)->toDateString(),
        ]))->assertOk()->json();

        $this->assertNotEmpty($payload);
        $this->assertSame($schedule->getKey(), $payload[0]['id'] ?? null);
        $this->assertSame(
            route("filament.admin.resources.{$slug}.view", ['record' => $schedule->getKey()]),
            $payload[0]['url'] ?? null
        );
    }

    public function test_inventory_dashboard_redirects_to_filament_inventory_levels_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $slug = InventoryLevelResource::getSlug();

        $this->get(route('admin.inventory.index'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));
    }

    public function test_admin_product_questions_pages_redirect_to_filament_product_question_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $question = ProductQuestion::create([
            'product_id' => $product->getKey(),
            'email' => 'question@example.com',
            'question' => 'Is this product suitable for outdoor use?',
            'status' => 'pending',
            'is_public' => true,
            'asked_at' => now(),
        ]);

        $slug = ProductQuestionResource::getSlug();

        $this->get(route('admin.products.questions.index'))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index"));

        $this->get(route('admin.products.questions.show', ['question' => $question->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.view", ['record' => $question->getKey()]));
    }

    public function test_product_availability_calendar_redirects_to_filament_product_availability_resource(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $slug = ProductAvailabilityResource::getSlug();

        $this->get(route('admin.products.availability.calendar', ['product' => $product->getKey()]))
            ->assertRedirect(route("filament.admin.resources.{$slug}.index", ['product_id' => $product->getKey()]));
    }
}

