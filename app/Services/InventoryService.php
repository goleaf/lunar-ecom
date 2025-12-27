<?php

namespace App\Services;

use App\Models\InventoryLevel;
use App\Models\InventoryTransaction;
use App\Models\LowStockAlert;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Models\ProductVariant;

/**
 * Service for managing inventory across multiple warehouses.
 */
class InventoryService
{
    /**
     * Check availability of a product variant across warehouses.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int|null  $warehouseId
     * @return array
     */
    public function checkAvailability(ProductVariant $variant, int $quantity, ?int $warehouseId = null): array
    {
        $query = InventoryLevel::where('product_variant_id', $variant->id);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $inventoryLevels = $query->with('warehouse')->get();

        $totalAvailable = 0;
        $warehouses = [];

        foreach ($inventoryLevels as $level) {
            $available = $level->available_quantity;
            $totalAvailable += $available;
            $warehouses[] = [
                'warehouse_id' => $level->warehouse_id,
                'warehouse_name' => $level->warehouse->name,
                'available_quantity' => $available,
                'total_quantity' => $level->quantity,
                'reserved_quantity' => $level->reserved_quantity,
            ];
        }

        return [
            'available' => $totalAvailable >= $quantity,
            'total_available' => $totalAvailable,
            'requested_quantity' => $quantity,
            'warehouses' => $warehouses,
            'can_fulfill' => $totalAvailable >= $quantity,
            'backorder_required' => $variant->backorder && $totalAvailable < $quantity,
        ];
    }

    /**
     * Reserve stock for an order or cart.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  string  $referenceType
     * @param  int|null  $referenceId
     * @param  int|null  $warehouseId
     * @param  int  $expirationMinutes
     * @return StockReservation
     * @throws \Exception
     */
    public function reserveStock(
        ProductVariant $variant,
        int $quantity,
        string $referenceType,
        ?int $referenceId = null,
        ?int $warehouseId = null,
        int $expirationMinutes = 15
    ): StockReservation {
        return DB::transaction(function () use ($variant, $quantity, $referenceType, $referenceId, $warehouseId, $expirationMinutes) {
            // Find warehouse(s) with available stock
            $warehouses = $this->findWarehousesWithStock($variant, $quantity, $warehouseId);

            if ($warehouses->isEmpty()) {
                throw new \Exception("Insufficient stock available for variant {$variant->id}");
            }

            $reservations = collect();
            $remainingQuantity = $quantity;

            foreach ($warehouses as $warehouse) {
                $level = InventoryLevel::where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $warehouse['warehouse_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$level) {
                    continue;
                }

                $available = $level->available_quantity;
                $reserveQuantity = min($remainingQuantity, $available);

                if ($reserveQuantity > 0) {
                    // Update inventory level
                    $level->reserved_quantity += $reserveQuantity;
                    $level->save();

                    // Create reservation
                    $reservation = StockReservation::create([
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $warehouse['warehouse_id'],
                        'inventory_level_id' => $level->id,
                        'quantity' => $reserveQuantity,
                        'reference_type' => $referenceType,
                        'reference_id' => $referenceId,
                        'session_id' => session()->getId(),
                        'user_id' => auth()->id(),
                        'expires_at' => now()->addMinutes($expirationMinutes),
                    ]);

                    // Log transaction
                    $this->logTransaction(
                        $variant,
                        $warehouse['warehouse_id'],
                        'reservation',
                        $reserveQuantity,
                        $level->quantity - $level->reserved_quantity,
                        $level->quantity - $level->reserved_quantity - $reserveQuantity,
                        $referenceType,
                        $referenceId
                    );

                    $reservations->push($reservation);
                    $remainingQuantity -= $reserveQuantity;
                }

                if ($remainingQuantity <= 0) {
                    break;
                }
            }

            if ($remainingQuantity > 0) {
                // Release what we reserved
                foreach ($reservations as $reservation) {
                    $this->releaseReservedStock($reservation);
                }
                $reservedQuantity = ($quantity - $remainingQuantity);
                throw new \Exception("Insufficient stock available. Only reserved {$reservedQuantity} of {$quantity}");
            }

            return $reservations->first();
        });
    }

    /**
     * Release reserved stock.
     *
     * @param  StockReservation|int  $reservation
     * @return void
     */
    public function releaseReservedStock(StockReservation|int $reservation): void
    {
        if (is_int($reservation)) {
            $reservation = StockReservation::findOrFail($reservation);
        }

        if ($reservation->is_released) {
            return;
        }

        DB::transaction(function () use ($reservation) {
            $level = $reservation->inventoryLevel;
            $level->reserved_quantity = max(0, $level->reserved_quantity - $reservation->quantity);
            $level->save();

            $reservation->update([
                'is_released' => true,
                'released_at' => now(),
            ]);

            // Log transaction
            $this->logTransaction(
                $reservation->productVariant,
                $reservation->warehouse_id,
                'release',
                -$reservation->quantity,
                $level->quantity - $level->reserved_quantity + $reservation->quantity,
                $level->quantity - $level->reserved_quantity,
                $reservation->reference_type,
                $reservation->reference_id
            );
        });
    }

    /**
     * Adjust inventory (manual adjustment).
     *
     * @param  ProductVariant  $variant
     * @param  int  $warehouseId
     * @param  int  $quantity
     * @param  string  $note
     * @param  int|null  $userId
     * @return InventoryLevel
     */
    public function adjustInventory(
        ProductVariant $variant,
        int $warehouseId,
        int $quantity,
        string $note = '',
        ?int $userId = null
    ): InventoryLevel {
        return DB::transaction(function () use ($variant, $warehouseId, $quantity, $note, $userId) {
            $level = InventoryLevel::firstOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouseId,
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'incoming_quantity' => 0,
                ]
            );

            $quantityBefore = $level->quantity;
            $level->quantity += $quantity;
            $level->save();

            // Log transaction
            $this->logTransaction(
                $variant,
                $warehouseId,
                'adjustment',
                $quantity,
                $quantityBefore,
                $level->quantity,
                null,
                null,
                $note,
                $userId
            );

            // Check for low stock alerts
            $this->checkLowStock($level);

            return $level->fresh();
        });
    }

    /**
     * Transfer stock between warehouses.
     *
     * @param  ProductVariant  $variant
     * @param  int  $fromWarehouseId
     * @param  int  $toWarehouseId
     * @param  int  $quantity
     * @param  string  $note
     * @param  int|null  $userId
     * @return array
     */
    public function transferStock(
        ProductVariant $variant,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        string $note = '',
        ?int $userId = null
    ): array {
        return DB::transaction(function () use ($variant, $fromWarehouseId, $toWarehouseId, $quantity, $note, $userId) {
            // Get source inventory level
            $fromLevel = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $fromWarehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fromLevel->available_quantity < $quantity) {
                throw new \Exception("Insufficient stock in source warehouse");
            }

            // Get or create destination inventory level
            $toLevel = InventoryLevel::firstOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $toWarehouseId,
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'incoming_quantity' => 0,
                ]
            );

            // Update quantities
            $fromLevel->quantity -= $quantity;
            $fromLevel->save();

            $toLevel->quantity += $quantity;
            $toLevel->save();

            // Log transactions
            $this->logTransaction(
                $variant,
                $fromWarehouseId,
                'transfer_out',
                -$quantity,
                $fromLevel->quantity + $quantity,
                $fromLevel->quantity,
                null,
                null,
                "Transfer to warehouse {$toWarehouseId}: {$note}",
                $userId
            );

            $this->logTransaction(
                $variant,
                $toWarehouseId,
                'transfer_in',
                $quantity,
                $toLevel->quantity - $quantity,
                $toLevel->quantity,
                null,
                null,
                "Transfer from warehouse {$fromWarehouseId}: {$note}",
                $userId
            );

            return [
                'from_level' => $fromLevel->fresh(),
                'to_level' => $toLevel->fresh(),
            ];
        });
    }

    /**
     * Find warehouses with available stock, ordered by priority.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int|null  $preferredWarehouseId
     * @return Collection
     */
    protected function findWarehousesWithStock(ProductVariant $variant, int $quantity, ?int $preferredWarehouseId = null): Collection
    {
        $query = InventoryLevel::where('product_variant_id', $variant->id)
            ->whereRaw('(quantity - reserved_quantity) > 0')
            ->with('warehouse')
            ->join('warehouses', 'inventory_levels.warehouse_id', '=', 'warehouses.id')
            ->where('warehouses.is_active', true)
            ->orderBy('warehouses.priority')
            ->orderByDesc('inventory_levels.quantity');

        if ($preferredWarehouseId) {
            $query->orderByRaw("CASE WHEN inventory_levels.warehouse_id = {$preferredWarehouseId} THEN 0 ELSE 1 END");
        }

        $levels = $query->get();

        $warehouses = collect();
        $remainingQuantity = $quantity;

        foreach ($levels as $level) {
            $available = $level->available_quantity;
            if ($available > 0) {
                $warehouses->push([
                    'warehouse_id' => $level->warehouse_id,
                    'warehouse_name' => $level->warehouse->name,
                    'available_quantity' => $available,
                ]);
                $remainingQuantity -= $available;
                if ($remainingQuantity <= 0) {
                    break;
                }
            }
        }

        return $warehouses;
    }

    /**
     * Log inventory transaction.
     *
     * @param  ProductVariant  $variant
     * @param  int  $warehouseId
     * @param  string  $type
     * @param  int  $quantity
     * @param  int|null  $quantityBefore
     * @param  int|null  $quantityAfter
     * @param  string|null  $referenceType
     * @param  int|null  $referenceId
     * @param  string  $note
     * @param  int|null  $userId
     * @return InventoryTransaction
     */
    protected function logTransaction(
        ProductVariant $variant,
        int $warehouseId,
        string $type,
        int $quantity,
        ?int $quantityBefore = null,
        ?int $quantityAfter = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        string $note = '',
        ?int $userId = null
    ): InventoryTransaction {
        $referenceNumber = null;
        if ($referenceType && $referenceId) {
            try {
                $reference = $referenceType::find($referenceId);
                if ($reference) {
                    $referenceNumber = $reference->reference ?? $reference->reference_number ?? $reference->id ?? null;
                }
            } catch (\Exception $e) {
                // Reference model not found or not accessible
                $referenceNumber = $referenceId;
            }
        }

        return InventoryTransaction::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_number' => $referenceNumber,
            'note' => $note,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Check for low stock and create alerts.
     *
     * @param  InventoryLevel  $level
     * @return void
     */
    protected function checkLowStock(InventoryLevel $level): void
    {
        if ($level->isLowStock() && $level->reorder_point > 0) {
            // Check if alert already exists
            $existingAlert = LowStockAlert::where('inventory_level_id', $level->id)
                ->where('is_resolved', false)
                ->first();

            if (!$existingAlert) {
                LowStockAlert::create([
                    'inventory_level_id' => $level->id,
                    'product_variant_id' => $level->product_variant_id,
                    'warehouse_id' => $level->warehouse_id,
                    'current_quantity' => $level->quantity,
                    'reorder_point' => $level->reorder_point,
                ]);
            }
        } else {
            // Resolve existing alerts if stock is now above reorder point
            LowStockAlert::where('inventory_level_id', $level->id)
                ->where('is_resolved', false)
                ->update([
                    'is_resolved' => true,
                    'resolved_at' => now(),
                ]);
        }
    }

    /**
     * Release expired reservations.
     *
     * @return int  Number of reservations released
     */
    public function releaseExpiredReservations(): int
    {
        $expired = StockReservation::expired()->get();
        $count = 0;

        foreach ($expired as $reservation) {
            $this->releaseReservedStock($reservation);
            $count++;
        }

        return $count;
    }

    /**
     * Get purchase order suggestions based on low stock.
     *
     * @param  int|null  $warehouseId
     * @return Collection
     */
    public function getPurchaseOrderSuggestions(?int $warehouseId = null): Collection
    {
        $query = InventoryLevel::with(['productVariant.product', 'warehouse'])
            ->lowStock()
            ->where('reorder_point', '>', 0)
            ->where('reorder_quantity', '>', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->map(function ($level) {
            $suggestedQuantity = max(
                $level->reorder_quantity,
                $level->reorder_point - $level->quantity + $level->reorder_quantity
            );

            return [
                'product_variant_id' => $level->product_variant_id,
                'product_name' => $level->productVariant->product->translateAttribute('name'),
                'variant_sku' => $level->productVariant->sku,
                'warehouse_id' => $level->warehouse_id,
                'warehouse_name' => $level->warehouse->name,
                'current_quantity' => $level->quantity,
                'reorder_point' => $level->reorder_point,
                'suggested_quantity' => $suggestedQuantity,
                'urgency' => $level->quantity <= 0 ? 'critical' : ($level->quantity < $level->reorder_point * 0.5 ? 'high' : 'medium'),
            ];
        });
    }
}

