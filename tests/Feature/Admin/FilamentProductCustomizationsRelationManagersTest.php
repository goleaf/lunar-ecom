<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\RelationManagers\CustomizationExamplesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\CustomizationsRelationManager;
use App\Models\CustomizationExample;
use App\Models\Product;
use App\Models\ProductCustomization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProductCustomizationsRelationManagersTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_customization_can_be_created_via_relation_manager(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        Livewire::test(CustomizationsRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => EditProduct::class,
        ])->callTableAction('create', null, [
            'customization_type' => 'text',
            'field_name' => 'engraving_text',
            'field_label' => 'Engraving Text',
            'is_required' => true,
            'price_modifier' => 10,
            'price_modifier_type' => 'fixed',
            'display_order' => 1,
            'is_active' => true,
            'show_in_preview' => true,
        ])->assertHasNoErrors();

        $this->assertDatabaseHas((new ProductCustomization())->getTable(), [
            'product_id' => $product->getKey(),
            'field_name' => 'engraving_text',
            'field_label' => 'Engraving Text',
            'customization_type' => 'text',
            'is_required' => 1,
            'is_active' => 1,
            'display_order' => 1,
        ]);
    }

    public function test_customization_example_can_be_created_via_relation_manager(): void
    {
        $this->seed();

        Storage::fake('public');

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $customization = ProductCustomization::create([
            'product_id' => $product->getKey(),
            'customization_type' => 'text',
            'field_name' => 'engraving_text',
            'field_label' => 'Engraving Text',
            'is_required' => false,
            'price_modifier' => 0,
            'price_modifier_type' => 'fixed',
            'display_order' => 0,
            'is_active' => true,
            'show_in_preview' => true,
        ]);

        Livewire::test(CustomizationExamplesRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => EditProduct::class,
        ])->callTableAction('create', null, [
            'customization_id' => $customization->getKey(),
            'title' => 'Example 1',
            'description' => 'An example customization',
            'example_image' => UploadedFile::fake()->image('example.jpg'),
            'customization_values' => json_encode(['engraving_text' => 'HELLO']),
            'display_order' => 1,
            'is_active' => true,
        ])->assertHasNoErrors();

        $example = CustomizationExample::query()
            ->where('product_id', $product->getKey())
            ->where('title', 'Example 1')
            ->firstOrFail();

        $this->assertNotEmpty($example->example_image);
        $this->assertTrue(Storage::disk('public')->exists($example->example_image));
    }
}

