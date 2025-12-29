<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\InventoryLevelResource;
use App\Filament\Resources\InventoryLevelResource\Pages\ListInventoryLevels;
use App\Models\InventoryLevel;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentInventoryLevelResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_level_index_and_edit_pages_render_for_staff(): void
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

        $slug = InventoryLevelResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $level->getKey(),
        ]))->assertOk();
    }

    public function test_inventory_level_table_actions_adjust_and_transfer_work(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $variant = ProductVariant::factory()->withoutPrices()->create(['stock' => 0]);

        $fromWarehouse = Warehouse::create([
            'name' => 'Warehouse A',
            'code' => 'WHA-' . Str::upper(Str::random(6)),
            'is_active' => true,
            'priority' => 0,
        ]);

        $toWarehouse = Warehouse::create([
            'name' => 'Warehouse B',
            'code' => 'WHB-' . Str::upper(Str::random(6)),
            'is_active' => true,
            'priority' => 1,
        ]);

        $level = InventoryLevel::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $fromWarehouse->id,
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

        Livewire::test(ListInventoryLevels::class)
            ->callTableAction('adjust_stock', $level, data: [
                'quantity' => 5,
                'reason' => 'Manual adjustment',
                'notes' => 'Test adjust',
            ]);

        $level = $level->fresh();
        $this->assertSame(15, (int) $level->quantity);
        $this->assertSame(15, (int) $variant->fresh()->stock);

        Livewire::test(ListInventoryLevels::class)
            ->callTableAction('transfer_stock', $level, data: [
                'to_warehouse_id' => $toWarehouse->id,
                'quantity' => 3,
                'notes' => 'Test transfer',
            ]);

        $fromLevel = $level->fresh();
        $toLevel = InventoryLevel::where('product_variant_id', $variant->id)
            ->where('warehouse_id', $toWarehouse->id)
            ->firstOrFail();

        $this->assertSame(12, (int) $fromLevel->quantity);
        $this->assertSame(3, (int) $toLevel->quantity);
        $this->assertSame(15, (int) $variant->fresh()->stock);
    }
}

