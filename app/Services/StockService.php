<?php

namespace App\Services;

use App\Models\InventoryLevel;
use App\Models\LowStockAlert;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Models\ProductVariant;

/**
 * Comprehensive stock management service.
 */
class StockService
{
    /**
     * Reserve stock for checkout.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  string|null  $sessionId
     * @param  int|null  $userId
     * @param  int|null  $warehouseId
     * @param  int  $expiryMinutes
     * @return StockReservation|null
     */
    public function reserveStock(
        ProductVariant $variant,
        int $quantity,
        ?string $sessionId = null,
        ?int $userId = null,
        ?int $warehouseId = null,
        int $expiryMinutes = 15
    ): ?StockReservation {
        // Find available warehouse
        $warehouse = $warehouseId 
            ? Warehouse::find($warehouseId)
            : $this->findAvailableWarehouse($variant, $quantity);

        if (!$warehouse) {
            return null;
        }

        // Get or create inventory level
        $inventoryLevel = InventoryLevel::firstOrCreate(
            [
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'reorder_point' => 10,
                'reorder_quantity' => 50,
                'status' => 'out_of_stock',
            ]
        );

        // Check available quantity
        if ($inventoryLevel->available_quantity < $quantity) {
            return null;
        }

        // Create reservation
        $reservation = StockReservation::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'inventory_level_id' => $inventoryLevel->id,
            'quantity' => $quantity,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'is_released' => false,
        ]);

        // Update reserved quantity
        $inventoryLevel->increment('reserved_quantity', $quantity);

        // Record movement
        $this->recordMovement(
            $variant,
            $warehouse,
            $inventoryLevel,
            'reservation',
            -$quantity,
            $inventoryLevel->quantity + $inventoryLevel->reserved_quantity - $quantity,
            $inventoryLevel->quantity + $inventoryLevel->reserved_quantity,
            $reservation,
            "Stock reserved for checkout"
        );

        return $reservation;
    }

    /**
     * Release stock reservation.
     *
     * @param  StockReservation  $reservation
     * @return bool
     */
    public function releaseReservation(StockReservation $reservation): bool
    {
        if ($reservation->is_released) {
            return false;
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'is_released' => true,
                'released_at' => now(),
            ]);

            if ($reservation->inventoryLevel) {
                $inventoryLevel = $reservation->inventoryLevel;
                $inventoryLevel->decrement('reserved_quantity', $reservation->quantity);

                // Record movement
                $this->recordMovement(
                    $reservation->productVariant,
                    $reservation->warehouse,
                    $inventoryLevel,
                    'release',
                    $reservation->quantity,
                    $inventoryLevel->quantity + $inventoryLevel->reserved_quantity + $reservation->quantity,
                    $inventoryLevel->quantity + $inventoryLevel->reserved_quantity,
                    $reservation,
                    "Stock reservation released"
                );
            }
        });

        return true;
    }

    /**
     * Release expired reservations.
     *
     * @return int
     */
    public function releaseExpiredReservations(): int
    {
        $expired = StockReservation::expired()->get();
        $count = 0;

        foreach ($expired as $reservation) {
            if ($this->releaseReservation($reservation)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Confirm reservation (convert to sale).
     *
     * @param  StockReservation  $reservation
     * @param  mixed  $order
     * @return bool
     */
    public function confirmReservation(StockReservation $reservation, $order): bool
    {
        if ($reservation->is_released) {
            return false;
        }

        DB::transaction(function () use ($reservation, $order) {
            $inventoryLevel = $reservation->inventoryLevel;
            
            // Decrease reserved quantity and actual quantity
            $inventoryLevel->decrement('reserved_quantity', $reservation->quantity);
            $inventoryLevel->decrement('quantity', $reservation->quantity);

            // Update reservation
            $reservation->update([
                'is_released' => true,
                'released_at' => now(),
                'reference_type' => get_class($order),
                'reference_id' => $order->id,
            ]);

            // Record movement
            $this->recordMovement(
                $reservation->productVariant,
                $reservation->warehouse,
                $inventoryLevel,
                'sale',
                -$reservation->quantity,
                $inventoryLevel->quantity + $reservation->quantity,
                $inventoryLevel->quantity,
                $order,
                "Stock sold - Order #{$order->reference}"
            );

            // Update variant stock (for backward compatibility)
            $variant = $reservation->productVariant;
            $variant->decrement('stock', $reservation->quantity);
        });

        return true;
    }

    /**
     * Adjust stock level.
     *
     * @param  ProductVariant  $variant
     * @param  int  $warehouseId
     * @param  int  $quantity
     * @param  string  $reason
     * @param  string|null  $notes
     * @return InventoryLevel
     */
    public function adjustStock(
        ProductVariant $variant,
        int $warehouseId,
        int $quantity,
        string $reason = 'Manual adjustment',
        ?string $notes = null
    ): InventoryLevel {
        $warehouse = Warehouse::findOrFail($warehouseId);
        
        $inventoryLevel = InventoryLevel::firstOrCreate(
            [
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'reorder_point' => 10,
                'reorder_quantity' => 50,
                'status' => 'out_of_stock',
            ]
        );

        $quantityBefore = $inventoryLevel->quantity;
        $inventoryLevel->increment('quantity', $quantity);
        $quantityAfter = $inventoryLevel->quantity;

        // Record movement
        $this->recordMovement(
            $variant,
            $warehouse,
            $inventoryLevel,
            $quantity > 0 ? 'adjustment' : 'adjustment',
            $quantity,
            $quantityBefore,
            $quantityAfter,
            null,
            $reason,
            $notes
        );

        // Update variant stock (aggregate from all warehouses)
        $this->syncVariantStock($variant);

        // Check for low stock alerts
        $this->checkLowStock($inventoryLevel);

        return $inventoryLevel->fresh();
    }

    /**
     * Transfer stock between warehouses.
     *
     * @param  ProductVariant  $variant
     * @param  int  $fromWarehouseId
     * @param  int  $toWarehouseId
     * @param  int  $quantity
     * @param  string|null  $notes
     * @return bool
     */
    public function transferStock(
        ProductVariant $variant,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        ?string $notes = null
    ): bool {
        if ($fromWarehouseId === $toWarehouseId) {
            return false;
        }

        $fromWarehouse = Warehouse::findOrFail($fromWarehouseId);
        $toWarehouse = Warehouse::findOrFail($toWarehouseId);

        $fromLevel = InventoryLevel::firstOrCreate(
            [
                'product_variant_id' => $variant->id,
                'warehouse_id' => $fromWarehouseId,
            ],
            ['quantity' => 0, 'reserved_quantity' => 0]
        );

        $toLevel = InventoryLevel::firstOrCreate(
            [
                'product_variant_id' => $variant->id,
                'warehouse_id' => $toWarehouseId,
            ],
            ['quantity' => 0, 'reserved_quantity' => 0]
        );

        if ($fromLevel->available_quantity < $quantity) {
            return false;
        }

        DB::transaction(function () use ($variant, $fromWarehouse, $toWarehouse, $fromLevel, $toLevel, $quantity, $notes) {
            // Decrease from source
            $fromBefore = $fromLevel->quantity;
            $fromLevel->decrement('quantity', $quantity);
            $fromAfter = $fromLevel->quantity;

            $this->recordMovement(
                $variant,
                $fromWarehouse,
                $fromLevel,
                'transfer',
                -$quantity,
                $fromBefore,
                $fromAfter,
                null,
                "Transfer to {$toWarehouse->name}",
                $notes
            );

            // Increase to destination
            $toBefore = $toLevel->quantity;
            $toLevel->increment('quantity', $quantity);
            $toAfter = $toLevel->quantity;

            $this->recordMovement(
                $variant,
                $toWarehouse,
                $toLevel,
                'transfer',
                $quantity,
                $toBefore,
                $toAfter,
                null,
                "Transfer from {$fromWarehouse->name}",
                $notes
            );
        });

        // Sync variant stock
        $this->syncVariantStock($variant);

        return true;
    }

    /**
     * Check for low stock and create alerts.
     *
     * @param  InventoryLevel  $inventoryLevel
     * @return LowStockAlert|null
     */
    public function checkLowStock(InventoryLevel $inventoryLevel): ?LowStockAlert
    {
        if (!$inventoryLevel->isLowStock()) {
            return null;
        }

        // Check if alert already exists
        $existingAlert = LowStockAlert::where('inventory_level_id', $inventoryLevel->id)
            ->where('is_resolved', false)
            ->first();

        if ($existingAlert) {
            // Update existing alert
            $existingAlert->update([
                'current_quantity' => $inventoryLevel->quantity,
            ]);
            return $existingAlert;
        }

        // Create new alert
        return LowStockAlert::create([
            'inventory_level_id' => $inventoryLevel->id,
            'product_variant_id' => $inventoryLevel->product_variant_id,
            'warehouse_id' => $inventoryLevel->warehouse_id,
            'current_quantity' => $inventoryLevel->quantity,
            'reorder_point' => $inventoryLevel->reorder_point,
            'is_resolved' => false,
            'notification_sent' => false,
        ]);
    }

    /**
     * Find available warehouse for a variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @return Warehouse|null
     */
    public function findAvailableWarehouse(ProductVariant $variant, int $quantity): ?Warehouse
    {
        // Get active warehouses ordered by priority
        $warehouses = Warehouse::active()->orderedByPriority()->get();

        foreach ($warehouses as $warehouse) {
            $inventoryLevel = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();

            if ($inventoryLevel && $inventoryLevel->available_quantity >= $quantity) {
                return $warehouse;
            }
        }

        return null;
    }

    /**
     * Get total available stock across all warehouses.
     *
     * @param  ProductVariant  $variant
     * @return int
     */
    public function getTotalAvailableStock(ProductVariant $variant): int
    {
        return InventoryLevel::where('product_variant_id', $variant->id)
            ->sum(DB::raw('quantity - reserved_quantity'));
    }

    /**
     * Sync variant stock from inventory levels.
     *
     * @param  ProductVariant  $variant
     * @return void
     */
    public function syncVariantStock(ProductVariant $variant): void
    {
        $totalStock = InventoryLevel::where('product_variant_id', $variant->id)
            ->sum('quantity');

        $variant->update(['stock' => $totalStock]);
    }

    /**
     * Record stock movement.
     *
     * @param  ProductVariant  $variant
     * @param  Warehouse|null  $warehouse
     * @param  InventoryLevel|null  $inventoryLevel
     * @param  string  $type
     * @param  int  $quantity
     * @param  int  $quantityBefore
     * @param  int  $quantityAfter
     * @param  mixed  $reference
     * @param  string|null  $reason
     * @param  string|null  $notes
     * @return StockMovement
     */
    protected function recordMovement(
        ProductVariant $variant,
        ?Warehouse $warehouse,
        ?InventoryLevel $inventoryLevel,
        string $type,
        int $quantity,
        int $quantityBefore,
        int $quantityAfter,
        $reference = null,
        ?string $reason = null,
        ?string $notes = null
    ): StockMovement {
        return StockMovement::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse?->id,
            'inventory_level_id' => $inventoryLevel?->id,
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'reference_number' => $reference?->reference ?? $reference?->id,
            'reason' => $reason,
            'notes' => $notes,
            'created_by' => auth()->id(),
            'movement_date' => now(),
        ]);
    }
}

