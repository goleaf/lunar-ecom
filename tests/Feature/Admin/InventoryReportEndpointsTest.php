<?php

namespace Tests\Feature\Admin;

use App\Models\InventoryLevel;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class InventoryReportEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_valuation_endpoint_returns_items_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->getKey(),
            'cost_price' => 10,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_active' => true,
            'priority' => 1,
        ]);

        InventoryLevel::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'quantity' => 3,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'damaged_quantity' => 0,
            'preorder_quantity' => 0,
        ]);

        $this->getJson(route('admin.inventory.reports.stock-valuation'))
            ->assertOk()
            ->assertJson([
                'total_value' => 30,
                'item_count' => 1,
            ])
            ->assertJsonStructure([
                'total_value',
                'item_count',
                'items' => [
                    [
                        'product_variant_id',
                        'product_name',
                        'variant_sku',
                        'warehouse_name',
                        'quantity',
                        'cost_price',
                        'total_value',
                    ],
                ],
            ])
            ->assertJsonPath('items.0.product_variant_id', $variant->getKey());
    }

    public function test_inventory_turnover_endpoint_returns_items_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->getKey(),
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_active' => true,
            'priority' => 1,
        ]);

        InventoryLevel::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'quantity' => 10,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'damaged_quantity' => 0,
            'preorder_quantity' => 0,
        ]);

        InventoryTransaction::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'type' => 'sale',
            'quantity' => -5,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        InventoryTransaction::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'type' => 'return',
            'quantity' => -1,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $this->getJson(route('admin.inventory.reports.inventory-turnover', [
            'days' => 30,
        ]))
            ->assertOk()
            ->assertJsonStructure([
                'period_days',
                'items' => [
                    [
                        'product_variant_id',
                        'product_name',
                        'variant_sku',
                        'current_stock',
                        'sales_quantity',
                        'returns_quantity',
                        'net_sales',
                        'turnover_rate',
                        'days_to_sell',
                    ],
                ],
            ])
            ->assertJsonPath('items.0.product_variant_id', $variant->getKey())
            ->assertJsonPath('items.0.sales_quantity', 5)
            ->assertJsonPath('items.0.returns_quantity', 1)
            ->assertJsonPath('items.0.net_sales', 4);
    }

    public function test_dead_stock_endpoint_returns_dead_stock_items_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->getKey(),
            'cost_price' => 10,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_active' => true,
            'priority' => 1,
        ]);

        InventoryLevel::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'quantity' => 10,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'damaged_quantity' => 0,
            'preorder_quantity' => 0,
        ]);

        $tx = InventoryTransaction::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'type' => 'sale',
            'quantity' => -1,
        ]);

        InventoryTransaction::whereKey($tx->getKey())->update([
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ]);

        $this->getJson(route('admin.inventory.reports.dead-stock', [
            'days' => 90,
        ]))
            ->assertOk()
            ->assertJsonStructure([
                'dead_stock_items',
                'total_value',
            ])
            ->assertJsonCount(1, 'dead_stock_items')
            ->assertJsonPath('dead_stock_items.0.product_variant_id', $variant->getKey());
    }

    public function test_fast_moving_items_endpoint_returns_items_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->getKey(),
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_active' => true,
            'priority' => 1,
        ]);

        InventoryTransaction::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'type' => 'sale',
            'quantity' => -20,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $this->getJson(route('admin.inventory.reports.fast-moving-items', [
            'days' => 30,
            'limit' => 10,
        ]))
            ->assertOk()
            ->assertJsonStructure([
                'period_days',
                'items' => [
                    [
                        'product_variant_id',
                        'product_name',
                        'variant_sku',
                        'total_sold',
                        'average_daily_sales',
                    ],
                ],
            ])
            ->assertJsonPath('items.0.product_variant_id', $variant->getKey());
    }
}

