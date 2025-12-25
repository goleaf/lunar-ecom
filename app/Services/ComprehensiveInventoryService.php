<?php

namespace App\Services;

use App\Models\InventoryLevel;
use App\Models\StockReservation;
use App\Models\StockMovement;
use App\Models\LowStockAlert;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Comprehensive Inventory Service - Complete inventory management.
 * 
 * Handles all inventory operations:
 * - Central stock tracking
 * - Multi-warehouse support
 * - Stock per warehouse
 * - Reserved stock (in carts / orders)
 * - Backorder handling
 * - Pre-order support
 * - Stock movement history
 * - Manual stock adjustments
 * - Low stock alerts
 * - Out-of-stock visibility rules
 */
class ComprehensiveInventoryService
{
    protected InventoryService $inventoryService;
    protected StockService $stockService;

    public function __construct(
        InventoryService $inventoryService,
        StockService $stockService
    ) {
        $this->inventoryService = $inventoryService;
        $this->stockService = $stockService;
    }

    /**
     * Get central stock tracking for a variant (aggregated across all warehouses).
     *
     * @param ProductVariant $variant
     * @return array
     */
    public function getCentralStock(ProductVariant $variant): array
    {
        $levels = InventoryLevel::where('product_variant_id', $variant->id)
            ->with('warehouse')
            ->get();

        $totalQuantity = $levels->sum('quantity');
        $totalReserved = $levels->sum('reserved_quantity');
        $totalAvailable = $totalQuantity - $totalReserved;
        $totalIncoming = $levels->sum('incoming_quantity');

        return [
            'variant_id' => $variant->id,
            'variant_sku' => $variant->sku,
            'total_quantity' => $totalQuantity,
            'total_reserved' => $totalReserved,
            'total_available' => $totalAvailable,
            'total_incoming' => $totalIncoming,
            'warehouses' => $levels->map(function ($level) {
                return [
                    'warehouse_id' => $level->warehouse_id,
                    'warehouse_name' => $level->warehouse->name,
                    'quantity' => $level->quantity,
                    'reserved_quantity' => $level->reserved_quantity,
                    'available_quantity' => $level->available_quantity,
                    'incoming_quantity' => $level->incoming_quantity,
                    'status' => $level->status,
                    'reorder_point' => $level->reorder_point,
                ];
            }),
            'status' => $this->determineOverallStatus($variant, $totalAvailable),
        ];
    }

    /**
     * Reserve stock for cart/order.
     *
     * @param ProductVariant $variant
     * @param int $quantity
     * @param string $referenceType
     * @param int|null $referenceId
     * @param int|null $warehouseId
     * @param int $expirationMinutes
     * @return StockReservation
     */
    public function reserveStock(
        ProductVariant $variant,
        int $quantity,
        string $referenceType,
        ?int $referenceId = null,
        ?int $warehouseId = null,
        int $expirationMinutes = 15
    ): StockReservation {
        return $this->inventoryService->reserveStock(
            $variant,
            $quantity,
            $referenceType,
            $referenceId,
            $warehouseId,
            $expirationMinutes
        );
    }

    /**
     * Release reserved stock.
     *
     * @param StockReservation|int $reservation
     * @return void
     */
    public function releaseReservedStock(StockReservation|int $reservation): void
    {
        $this->inventoryService->releaseReservedStock($reservation);
    }

    /**
     * Handle backorder for a variant.
     *
     * @param ProductVariant $variant
     * @param int $quantity
     * @param int|null $warehouseId
     * @return array
     */
    public function handleBackorder(
        ProductVariant $variant,
        int $quantity,
        ?int $warehouseId = null
    ): array {
        if (!$variant->backorder || $variant->backorder <= 0) {
            throw new \Exception('Backorder not enabled for this variant');
        }

        // Check if we can fulfill from available stock
        $availability = $this->inventoryService->checkAvailability($variant, $quantity, $warehouseId);

        if ($availability['available']) {
            return [
                'fulfilled' => true,
                'backorder_required' => false,
                'quantity_fulfilled' => $quantity,
                'backorder_quantity' => 0,
            ];
        }

        // Calculate backorder quantity
        $backorderQuantity = $quantity - $availability['total_available'];

        // Update inventory level status to backorder if needed
        if ($warehouseId) {
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if ($level && $level->available_quantity <= 0) {
                $level->status = 'backorder';
                $level->save();
            }
        }

        return [
            'fulfilled' => false,
            'backorder_required' => true,
            'quantity_fulfilled' => $availability['total_available'],
            'backorder_quantity' => $backorderQuantity,
        ];
    }

    /**
     * Create pre-order for a variant.
     *
     * @param ProductVariant $variant
     * @param int $quantity
     * @param Carbon|null $releaseDate
     * @param string $referenceType
     * @param int|null $referenceId
     * @return array
     */
    public function createPreorder(
        ProductVariant $variant,
        int $quantity,
        ?Carbon $releaseDate = null,
        string $referenceType = 'preorder',
        ?int $referenceId = null
    ): array {
        if (!$variant->preorder_enabled) {
            throw new \Exception('Pre-order not enabled for this variant');
        }

        $releaseDate = $releaseDate ?? $variant->preorder_release_date;

        if (!$releaseDate) {
            throw new \Exception('Pre-order release date is required');
        }

        // Create pre-order reservation (doesn't reserve actual stock)
        $reservation = StockReservation::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => null, // Pre-orders don't have warehouse yet
            'inventory_level_id' => null,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'session_id' => session()->getId(),
            'user_id' => auth()->id(),
            'expires_at' => $releaseDate, // Expires on release date
        ]);

        // Record pre-order movement
        StockMovement::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => null,
            'inventory_level_id' => null,
            'type' => 'preorder',
            'quantity' => $quantity,
            'quantity_before' => 0,
            'quantity_after' => 0,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => 'Pre-order created',
            'notes' => "Pre-order release date: {$releaseDate->format('Y-m-d H:i:s')}",
            'created_by' => auth()->id(),
            'movement_date' => now(),
        ]);

        return [
            'reservation_id' => $reservation->id,
            'quantity' => $quantity,
            'release_date' => $releaseDate,
            'status' => 'preorder',
        ];
    }

    /**
     * Fulfill pre-order when stock arrives.
     *
     * @param StockReservation $preorderReservation
     * @param int $warehouseId
     * @return bool
     */
    public function fulfillPreorder(StockReservation $preorderReservation, int $warehouseId): bool
    {
        if ($preorderReservation->is_released) {
            return false;
        }

        $variant = $preorderReservation->productVariant;

        // Check if stock is available
        $availability = $this->inventoryService->checkAvailability(
            $variant,
            $preorderReservation->quantity,
            $warehouseId
        );

        if (!$availability['available']) {
            return false;
        }

        // Reserve actual stock
        $actualReservation = $this->reserveStock(
            $variant,
            $preorderReservation->quantity,
            $preorderReservation->reference_type,
            $preorderReservation->reference_id,
            $warehouseId
        );

        // Mark pre-order as fulfilled
        $preorderReservation->update([
            'is_released' => true,
            'released_at' => now(),
            'reference_type' => get_class($actualReservation),
            'reference_id' => $actualReservation->id,
        ]);

        return true;
    }

    /**
     * Manually adjust stock level.
     *
     * @param ProductVariant $variant
     * @param int $warehouseId
     * @param int $quantity Positive to add, negative to subtract
     * @param string $reason
     * @param string|null $notes
     * @param int|null $userId
     * @return InventoryLevel
     */
    public function adjustStock(
        ProductVariant $variant,
        int $warehouseId,
        int $quantity,
        string $reason = 'Manual adjustment',
        ?string $notes = null,
        ?int $userId = null
    ): InventoryLevel {
        return $this->stockService->adjustStock(
            $variant,
            $warehouseId,
            $quantity,
            $reason,
            $notes
        );
    }

    /**
     * Get stock movement history for a variant.
     *
     * @param ProductVariant $variant
     * @param int|null $warehouseId
     * @param int $limit
     * @return Collection
     */
    public function getStockMovementHistory(
        ProductVariant $variant,
        ?int $warehouseId = null,
        int $limit = 50
    ): Collection {
        $query = StockMovement::where('product_variant_id', $variant->id)
            ->with(['warehouse', 'creator', 'reference'])
            ->orderBy('movement_date', 'desc')
            ->limit($limit);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }

    /**
     * Get low stock alerts.
     *
     * @param bool $unresolvedOnly
     * @param int|null $warehouseId
     * @return Collection
     */
    public function getLowStockAlerts(
        bool $unresolvedOnly = true,
        ?int $warehouseId = null
    ): Collection {
        $query = LowStockAlert::with(['inventoryLevel.productVariant.product', 'warehouse']);

        if ($unresolvedOnly) {
            $query->where('is_resolved', false);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Check and create low stock alerts.
     *
     * @param InventoryLevel $level
     * @return LowStockAlert|null
     */
    public function checkLowStock(InventoryLevel $level): ?LowStockAlert
    {
        return $this->stockService->checkLowStock($level);
    }

    /**
     * Determine overall stock status for a variant.
     *
     * @param ProductVariant $variant
     * @param int $totalAvailable
     * @return string
     */
    protected function determineOverallStatus(ProductVariant $variant, int $totalAvailable): string
    {
        if ($variant->preorder_enabled && $variant->preorder_release_date && $variant->preorder_release_date->isFuture()) {
            return 'preorder';
        }

        if ($totalAvailable <= 0) {
            if ($variant->backorder && $variant->backorder > 0) {
                return 'backorder';
            }
            return 'out_of_stock';
        }

        $threshold = $variant->low_stock_threshold ?? 10;
        if ($totalAvailable <= $threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Sync variant stock from inventory levels (central stock tracking).
     *
     * @param ProductVariant $variant
     * @return void
     */
    public function syncVariantStock(ProductVariant $variant): void
    {
        $totalStock = $this->getTotalAvailableStock($variant);
        $variant->stock = $totalStock;
        $variant->save();
    }

    /**
     * Get total available stock for a variant.
     *
     * @param ProductVariant $variant
     * @return int
     */
    protected function getTotalAvailableStock(ProductVariant $variant): int
    {
        return InventoryLevel::where('product_variant_id', $variant->id)
            ->sum(DB::raw('quantity - reserved_quantity'));
    }

    /**
     * Transfer stock between warehouses.
     *
     * @param ProductVariant $variant
     * @param int $fromWarehouseId
     * @param int $toWarehouseId
     * @param int $quantity
     * @param string|null $notes
     * @return bool
     */
    public function transferStock(
        ProductVariant $variant,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        ?string $notes = null
    ): bool {
        return $this->stockService->transferStock(
            $variant,
            $fromWarehouseId,
            $toWarehouseId,
            $quantity,
            $notes
        );
    }

    /**
     * Get inventory summary for dashboard.
     *
     * @return array
     */
    public function getInventorySummary(): array
    {
        $totalVariants = ProductVariant::count();
        $totalStock = InventoryLevel::sum('quantity');
        $totalReserved = InventoryLevel::sum('reserved_quantity');
        $totalAvailable = $totalStock - $totalReserved;

        $lowStockCount = InventoryLevel::lowStock()->count();
        $outOfStockCount = InventoryLevel::outOfStock()->count();
        $activeAlerts = LowStockAlert::where('is_resolved', false)->count();

        return [
            'total_variants' => $totalVariants,
            'total_stock' => $totalStock,
            'total_reserved' => $totalReserved,
            'total_available' => $totalAvailable,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'active_alerts' => $activeAlerts,
        ];
    }
}

