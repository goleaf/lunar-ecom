<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\StockMovementResource;
use App\Models\InventoryLevel;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class FilamentStockMovementResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_movement_index_and_view_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $variant = ProductVariant::factory()->withoutPrices()->create(['stock' => 0]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH-' . Str::upper(Str::random(6)),
            'is_active' => true,
            'priority' => 0,
        ]);

        $level = InventoryLevel::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'damaged_quantity' => 0,
            'preorder_quantity' => 0,
            'reorder_point' => 5,
            'safety_stock_level' => 0,
            'reorder_quantity' => 50,
            'status' => 'in_stock',
        ]);

        $movement = StockMovement::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'inventory_level_id' => $level->id,
            'type' => 'manual_adjustment',
            'quantity' => 5,
            'quantity_before' => 10,
            'quantity_after' => 15,
            'reason' => 'Test movement',
            'notes' => 'Test notes',
            'actor_type' => 'staff',
            'actor_identifier' => (string) $staff->id,
            'movement_date' => now(),
        ]);

        $slug = StockMovementResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $movement->getKey(),
        ]))->assertOk();
    }
}

