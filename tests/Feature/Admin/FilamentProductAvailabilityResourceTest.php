<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductAvailabilityResource;
use App\Filament\Resources\ProductAvailabilityResource\Pages\CreateProductAvailability;
use App\Models\Product;
use App\Models\ProductAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProductAvailabilityResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_availability_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $availability = ProductAvailability::factory()->create();

        $slug = ProductAvailabilityResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $availability->getKey()]))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $availability->getKey()]))->assertOk();
    }

    public function test_product_availability_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        Livewire::test(CreateProductAvailability::class)
            ->set('data.product_id', $product->id)
            ->set('data.availability_type', 'always_available')
            ->set('data.is_active', true)
            ->set('data.timezone', 'UTC')
            ->set('data.priority', 0)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new ProductAvailability())->getTable(), 1);
    }
}

