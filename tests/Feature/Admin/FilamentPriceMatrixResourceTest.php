<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\PriceMatrixResource;
use App\Filament\Resources\PriceMatrixResource\Pages\CreatePriceMatrix;
use App\Filament\Resources\PriceMatrixResource\Pages\ListPriceMatrices;
use App\Models\PriceMatrix;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentPriceMatrixResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_matrix_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $matrix = PriceMatrix::factory()->create();

        $slug = PriceMatrixResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $matrix->getKey(),
        ]))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $matrix->getKey(),
        ]))->assertOk();
    }

    public function test_price_matrix_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->published()->create();

        Livewire::test(CreatePriceMatrix::class)
            ->set('data.product_id', $product->id)
            ->set('data.matrix_type', 'quantity')
            ->set('data.rules', '[]')
            ->set('data.is_active', true)
            ->call('create');

        $this->assertDatabaseCount((new PriceMatrix())->getTable(), 1);
    }

    public function test_price_matrix_can_be_approved_via_table_action(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $matrix = PriceMatrix::factory()->create([
            'requires_approval' => true,
            'approval_status' => 'pending',
        ]);

        Livewire::test(ListPriceMatrices::class)
            ->callTableAction('approve', $matrix);

        $this->assertDatabaseHas((new PriceMatrix())->getTable(), [
            'id' => $matrix->id,
            'approval_status' => 'approved',
        ]);
    }
}

