<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\BundleResource;
use App\Filament\Resources\BundleResource\Pages\CreateBundle;
use App\Models\Bundle;
use App\Models\BundleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentBundleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundle_index_create_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $bundle = Bundle::create([
            'product_id' => $product->id,
            'name' => 'Test Bundle',
            'slug' => 'test-bundle-' . Str::lower(Str::random(8)),
            'pricing_type' => 'fixed',
            'inventory_type' => 'component',
            'stock' => 0,
            'min_quantity' => 1,
            'is_active' => true,
            'is_featured' => false,
            'display_order' => 0,
            'allow_customization' => false,
            'show_individual_prices' => true,
            'show_savings' => true,
        ]);

        BundleItem::create([
            'bundle_id' => $bundle->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'min_quantity' => 1,
            'is_required' => true,
            'is_default' => true,
            'display_order' => 0,
        ]);

        $slug = BundleResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $bundle->getKey(),
        ]))->assertOk();
    }

    public function test_bundle_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $itemKey = (string) Str::uuid();

        $component = Livewire::test(CreateBundle::class)
            ->set('data.product_id', $product->id)
            ->set('data.name', 'Bundle ' . Str::title(Str::random(8)))
            ->set('data.pricing_type', 'fixed')
            ->set('data.inventory_type', 'component')
            ->set('data.items', [
                $itemKey => [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                    'min_quantity' => 1,
                    'is_required' => true,
                    'is_default' => true,
                    'display_order' => 0,
                ],
            ])
            ->call('create');

        $this->assertTrue(
            $component->instance()->getErrorBag()->isEmpty(),
            json_encode($component->instance()->getErrorBag()->toArray(), JSON_PRETTY_PRINT)
        );

        $this->assertDatabaseCount((new Bundle())->getTable(), 1);
        $this->assertDatabaseCount((new BundleItem())->getTable(), 1);
    }
}

