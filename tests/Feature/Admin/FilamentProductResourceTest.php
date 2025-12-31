<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProductResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $slug = ProductResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $product->getKey()]))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $product->getKey()]))->assertOk();
    }

    public function test_product_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $productType = ProductType::factory()->create();

        Livewire::test(CreateProduct::class)
            ->set('data.product_type_id', $productType->id)
            ->set('data.status', Product::STATUS_DRAFT)
            ->set('data.visibility', Product::VISIBILITY_PRIVATE)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new Product())->getTable(), 1);
    }
}

