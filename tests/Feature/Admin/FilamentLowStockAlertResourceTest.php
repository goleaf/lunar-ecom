<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\LowStockAlertResource;
use App\Filament\Resources\LowStockAlertResource\Pages\ListLowStockAlerts;
use App\Models\InventoryLevel;
use App\Models\LowStockAlert;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentLowStockAlertResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_alert_index_and_view_pages_render_for_staff(): void
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
            'quantity' => 2,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'damaged_quantity' => 0,
            'preorder_quantity' => 0,
            'reorder_point' => 5,
            'safety_stock_level' => 0,
            'reorder_quantity' => 50,
            'status' => 'low_stock',
        ]);

        $alert = LowStockAlert::create([
            'inventory_level_id' => $level->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'current_quantity' => 2,
            'reorder_point' => 5,
            'is_resolved' => false,
            'notification_sent' => false,
        ]);

        $slug = LowStockAlertResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $alert->getKey(),
        ]))->assertOk();
    }

    public function test_low_stock_alert_can_be_resolved_via_table_action(): void
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
            'quantity' => 1,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'damaged_quantity' => 0,
            'preorder_quantity' => 0,
            'reorder_point' => 5,
            'safety_stock_level' => 0,
            'reorder_quantity' => 50,
            'status' => 'low_stock',
        ]);

        $alert = LowStockAlert::create([
            'inventory_level_id' => $level->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'current_quantity' => 1,
            'reorder_point' => 5,
            'is_resolved' => false,
            'notification_sent' => false,
        ]);

        Livewire::test(ListLowStockAlerts::class)
            ->callTableAction('resolve', $alert);

        $alert = $alert->fresh();
        $this->assertTrue($alert->is_resolved);
        $this->assertNotNull($alert->resolved_at);
        $this->assertNull($alert->resolved_by);
    }
}

