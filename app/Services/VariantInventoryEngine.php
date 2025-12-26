<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\InventoryLevel;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Variant Inventory Engine.
 * 
 * Variant-centric inventory management with comprehensive stock tracking.
 * 
 * Core Fields:
 * - On-hand quantity
 * - Reserved quantity
 * - Available quantity (computed)
 * - Incoming quantity
 * - Damaged quantity
 * - Backorder limit
 * - Preorder quantity
 * - Safety stock level
 */
class VariantInventoryEngine
{
    /**
     * Get on-hand quantity for variant.
     * 
     * On-hand = Total quantity - Damaged quantity
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getOnHandQuantity(ProductVariant $variant, ?int $warehouseId = null): int
    {
        if ($variant->virtual_stock ?? false) {
            return 999999; // Unlimited for virtual stock
        }

        if ($warehouseId) {
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->first();
            
            return $level ? $level->on_hand_quantity : 0;
        }

        // Total across all warehouses
        return InventoryLevel::where('product_variant_id', $variant->id)
            ->get()
            ->sum(fn($level) => $level->on_hand_quantity);
    }

    /**
     * Get reserved quantity for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getReservedQuantity(ProductVariant $variant, ?int $warehouseId = null): int
    {
        if ($variant->virtual_stock ?? false) {
            return 0;
        }

        if ($warehouseId) {
            return InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->value('reserved_quantity') ?? 0;
        }

        return InventoryLevel::where('product_variant_id', $variant->id)
            ->sum('reserved_quantity');
    }

    /**
     * Get available quantity for variant.
     * 
     * Available = On-hand - Reserved - Damaged
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getAvailableQuantity(ProductVariant $variant, ?int $warehouseId = null): int
    {
        if ($variant->virtual_stock ?? false) {
            return 999999; // Unlimited
        }

        if ($warehouseId) {
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->first();
            
            return $level ? $level->available_quantity : 0;
        }

        // Total across all warehouses
        return InventoryLevel::where('product_variant_id', $variant->id)
            ->get()
            ->sum(fn($level) => $level->available_quantity);
    }

    /**
     * Get incoming quantity for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getIncomingQuantity(ProductVariant $variant, ?int $warehouseId = null): int
    {
        if ($variant->virtual_stock ?? false) {
            return 0;
        }

        if ($warehouseId) {
            return InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->value('incoming_quantity') ?? 0;
        }

        return InventoryLevel::where('product_variant_id', $variant->id)
            ->sum('incoming_quantity');
    }

    /**
     * Get damaged quantity for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getDamagedQuantity(ProductVariant $variant, ?int $warehouseId = null): int
    {
        if ($variant->virtual_stock ?? false) {
            return 0;
        }

        if ($warehouseId) {
            return InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->value('damaged_quantity') ?? 0;
        }

        return InventoryLevel::where('product_variant_id', $variant->id)
            ->sum('damaged_quantity');
    }

    /**
     * Get preorder quantity for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getPreorderQuantity(ProductVariant $variant, ?int $warehouseId = null): int
    {
        if ($variant->virtual_stock ?? false) {
            return 0;
        }

        if ($warehouseId) {
            return InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->value('preorder_quantity') ?? 0;
        }

        return InventoryLevel::where('product_variant_id', $variant->id)
            ->sum('preorder_quantity');
    }

    /**
     * Get backorder limit for variant.
     * 
     * Returns warehouse-specific limit if provided, otherwise variant-level limit.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int|null
     */
    public function getBackorderLimit(ProductVariant $variant, ?int $warehouseId = null): ?int
    {
        if ($warehouseId) {
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->first();
            
            // Warehouse-specific limit takes precedence
            if ($level && $level->backorder_limit !== null) {
                return $level->backorder_limit;
            }
        }

        // Fallback to variant-level limit
        return $variant->backorder_limit;
    }

    /**
     * Get safety stock level for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getSafetyStockLevel(ProductVariant $variant, ?int $warehouseId = null): int
    {
        if ($warehouseId) {
            return InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->value('safety_stock_level') ?? 0;
        }

        // Return minimum safety stock across all warehouses
        return InventoryLevel::where('product_variant_id', $variant->id)
            ->min('safety_stock_level') ?? 0;
    }

    /**
     * Get complete inventory summary for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return array
     */
    public function getInventorySummary(ProductVariant $variant, ?int $warehouseId = null): array
    {
        $onHand = $this->getOnHandQuantity($variant, $warehouseId);
        $reserved = $this->getReservedQuantity($variant, $warehouseId);
        $available = $this->getAvailableQuantity($variant, $warehouseId);
        $incoming = $this->getIncomingQuantity($variant, $warehouseId);
        $damaged = $this->getDamagedQuantity($variant, $warehouseId);
        $preorder = $this->getPreorderQuantity($variant, $warehouseId);
        $safetyStock = $this->getSafetyStockLevel($variant, $warehouseId);
        $backorderLimit = $this->getBackorderLimit($variant, $warehouseId);

        return [
            'on_hand_quantity' => $onHand,
            'reserved_quantity' => $reserved,
            'available_quantity' => $available,
            'incoming_quantity' => $incoming,
            'damaged_quantity' => $damaged,
            'preorder_quantity' => $preorder,
            'safety_stock_level' => $safetyStock,
            'backorder_limit' => $backorderLimit,
            'total_quantity' => $onHand + $incoming,
            'sellable_quantity' => $available + $preorder,
            'is_below_safety_stock' => $available < $safetyStock,
            'is_low_stock' => $this->isLowStock($variant, $warehouseId),
            'is_out_of_stock' => $available <= 0,
            'can_backorder' => $this->canBackorder($variant, 1, $available, $warehouseId),
        ];
    }

    /**
     * Check if variant is low stock.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return bool
     */
    public function isLowStock(ProductVariant $variant, ?int $warehouseId = null): bool
    {
        if ($variant->virtual_stock ?? false) {
            return false;
        }

        $available = $this->getAvailableQuantity($variant, $warehouseId);
        $threshold = $variant->low_stock_threshold ?? 10;

        return $available > 0 && $available <= $threshold;
    }

    /**
     * Check if variant can backorder.
     *
     * @param  ProductVariant  $variant
     * @param  int  $requestedQuantity
     * @param  int  $availableQuantity
     * @param  int|null  $warehouseId
     * @return bool
     */
    public function canBackorder(
        ProductVariant $variant,
        int $requestedQuantity,
        int $availableQuantity,
        ?int $warehouseId = null
    ): bool {
        $backorderAllowed = $variant->backorder_allowed ?? false;

        if (!$backorderAllowed) {
            return false;
        }

        $backorderLimit = $this->getBackorderLimit($variant, $warehouseId);

        if ($backorderLimit === null) {
            return true; // Unlimited backorder
        }

        $backorderNeeded = max(0, $requestedQuantity - $availableQuantity);
        return $backorderNeeded <= $backorderLimit;
    }

    /**
     * Update inventory level for variant at warehouse.
     *
     * @param  ProductVariant  $variant
     * @param  int  $warehouseId
     * @param  array  $data
     * @return InventoryLevel
     */
    public function updateInventoryLevel(
        ProductVariant $variant,
        int $warehouseId,
        array $data
    ): InventoryLevel {
        return DB::transaction(function () use ($variant, $warehouseId, $data) {
            $level = InventoryLevel::updateOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouseId,
                ],
                $data
            );

            $level->updateStatus();
            $level->save();

            return $level->fresh();
        });
    }

    /**
     * Adjust on-hand quantity.
     *
     * @param  ProductVariant  $variant
     * @param  int  $warehouseId
     * @param  int  $quantity
     * @param  string  $reason
     * @return InventoryLevel
     */
    public function adjustOnHandQuantity(
        ProductVariant $variant,
        int $warehouseId,
        int $quantity,
        string $reason = 'manual_adjustment'
    ): InventoryLevel {
        return DB::transaction(function () use ($variant, $warehouseId, $quantity, $reason) {
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrCreate([
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouseId,
                ]);

            $level->increment('quantity', $quantity);
            $level->updateStatus();
            $level->save();

            // Record transaction
            $this->recordTransaction($variant, $warehouseId, $quantity, 'adjustment', $reason);

            return $level->fresh();
        });
    }

    /**
     * Record damaged quantity.
     *
     * @param  ProductVariant  $variant
     * @param  int  $warehouseId
     * @param  int  $quantity
     * @param  string  $reason
     * @return InventoryLevel
     */
    public function recordDamagedQuantity(
        ProductVariant $variant,
        int $warehouseId,
        int $quantity,
        string $reason = 'damaged'
    ): InventoryLevel {
        return DB::transaction(function () use ($variant, $warehouseId, $quantity, $reason) {
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrCreate([
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouseId,
                ]);

            $level->increment('damaged_quantity', $quantity);
            $level->decrement('quantity', $quantity); // Remove from on-hand
            $level->updateStatus();
            $level->save();

            // Record transaction
            $this->recordTransaction($variant, $warehouseId, -$quantity, 'damaged', $reason);

            return $level->fresh();
        });
    }

    /**
     * Record incoming quantity.
     *
     * @param  ProductVariant  $variant
     * @param  int  $warehouseId
     * @param  int  $quantity
     * @param  string  $reason
     * @return InventoryLevel
     */
    public function recordIncomingQuantity(
        ProductVariant $variant,
        int $warehouseId,
        int $quantity,
        string $reason = 'purchase_order'
    ): InventoryLevel {
        return DB::transaction(function () use ($variant, $warehouseId, $quantity, $reason) {
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrCreate([
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouseId,
                ]);

            $level->increment('incoming_quantity', $quantity);
            $level->save();

            return $level->fresh();
        });
    }

    /**
     * Receive incoming stock (move from incoming to on-hand).
     *
     * @param  ProductVariant  $variant
     * @param  int  $warehouseId
     * @param  int  $quantity
     * @param  string  $reason
     * @return InventoryLevel
     */
    public function receiveIncomingStock(
        ProductVariant $variant,
        int $warehouseId,
        int $quantity,
        string $reason = 'received'
    ): InventoryLevel {
        return DB::transaction(function () use ($variant, $warehouseId, $quantity, $reason) {
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($level->incoming_quantity < $quantity) {
                throw new \RuntimeException('Cannot receive more than incoming quantity.');
            }

            $level->decrement('incoming_quantity', $quantity);
            $level->increment('quantity', $quantity);
            $level->updateStatus();
            $level->save();

            // Record transaction
            $this->recordTransaction($variant, $warehouseId, $quantity, 'received', $reason);

            return $level->fresh();
        });
    }

    /**
     * Record transaction (helper method).
     *
     * @param  ProductVariant  $variant
     * @param  int  $warehouseId
     * @param  int  $quantity
     * @param  string  $type
     * @param  string  $reason
     * @return void
     */
    protected function recordTransaction(
        ProductVariant $variant,
        int $warehouseId,
        int $quantity,
        string $type,
        string $reason
    ): void {
        // Create inventory transaction if model exists
        if (class_exists(\App\Models\InventoryTransaction::class)) {
            \App\Models\InventoryTransaction::create([
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'type' => $type,
                'reason' => $reason,
            ]);
        }
    }
}


