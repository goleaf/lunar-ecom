<?php

namespace Tests\Feature\Frontend;

use App\Models\InventoryLevel;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReservationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserve_creates_reservation_and_updates_inventory_level(): void
    {
        $variant = ProductVariant::factory()->create();

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_active' => true,
            'priority' => 1,
        ]);

        $level = InventoryLevel::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'quantity' => 10,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'damaged_quantity' => 0,
            'preorder_quantity' => 0,
        ]);

        $this->postJson(route('frontend.stock-reservations.reserve'), [
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
            'warehouse_id' => $warehouse->getKey(),
            'expiration_minutes' => 5,
        ])
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'reservation' => ['id', 'product_variant_id', 'warehouse_id', 'quantity', 'expires_at'],
                'expires_at',
            ]);

        $level->refresh();
        $this->assertSame(2, $level->reserved_quantity);

        $this->assertDatabaseCount((new StockReservation())->getTable(), 1);
        $this->assertDatabaseHas((new StockReservation())->getTable(), [
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'inventory_level_id' => $level->getKey(),
            'quantity' => 2,
            'is_released' => 0,
        ]);
    }

    public function test_release_marks_reservation_released_and_decrements_reserved_quantity(): void
    {
        $variant = ProductVariant::factory()->create();

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_active' => true,
            'priority' => 1,
        ]);

        $level = InventoryLevel::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'quantity' => 10,
            'reserved_quantity' => 2,
            'incoming_quantity' => 0,
            'damaged_quantity' => 0,
            'preorder_quantity' => 0,
        ]);

        $reservation = StockReservation::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'inventory_level_id' => $level->getKey(),
            'quantity' => 2,
            'reference_type' => 'Cart',
            'reference_id' => null,
            'session_id' => session()->getId(),
            'user_id' => null,
            'expires_at' => now()->addMinutes(5),
            'is_released' => false,
        ]);

        $this->postJson(route('frontend.stock-reservations.release'), [
            'reservation_id' => $reservation->getKey(),
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Stock reservation released',
            ]);

        $reservation->refresh();
        $level->refresh();

        $this->assertTrue((bool) $reservation->is_released);
        $this->assertSame(0, $level->reserved_quantity);
    }

    public function test_reserve_returns_422_when_insufficient_stock(): void
    {
        $variant = ProductVariant::factory()->create();

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_active' => true,
            'priority' => 1,
        ]);

        InventoryLevel::create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'quantity' => 1,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'damaged_quantity' => 0,
            'preorder_quantity' => 0,
        ]);

        $this->postJson(route('frontend.stock-reservations.reserve'), [
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
            'warehouse_id' => $warehouse->getKey(),
        ])
            ->assertStatus(422)
            ->assertJsonStructure([
                'message',
            ]);
    }
}

