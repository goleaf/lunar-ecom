<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\InventoryLevel;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Comprehensive variant inventory service.
 * 
 * Handles:
 * - Stock quantity management
 * - Reserved quantity tracking
 * - Available quantity calculation
 * - Backorder management
 * - Preorder support
 * - Min/max order quantities
 * - Low-stock thresholds
 * - Stock status calculation
 * - Multi-warehouse inventory
 * - Virtual stock (services/digital)
 */
class VariantInventoryService
{
    /**
     * Get available stock for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getAvailableStock(ProductVariant $variant, ?int $warehouseId = null): int
    {
        // Virtual stock (services/digital) - always available
        if ($variant->is_virtual) {
            return 999999; // Unlimited
        }

        if ($warehouseId) {
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->first();
            
            if (!$level) {
                return 0;
            }

            return max(0, $level->quantity - $level->reserved_quantity);
        }

        // Total across all warehouses
        return InventoryLevel::where('product_variant_id', $variant->id)
            ->get()
            ->sum(function ($level) {
                return max(0, $level->quantity - $level->reserved_quantity);
            });
    }

    /**
     * Get stock quantity (total, not available).
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getStockQuantity(ProductVariant $variant, ?int $warehouseId = null): int
    {
        if ($variant->is_virtual) {
            return 999999;
        }

        if ($warehouseId) {
            return InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->value('quantity') ?? 0;
        }

        return InventoryLevel::where('product_variant_id', $variant->id)
            ->sum('quantity');
    }

    /**
     * Get reserved quantity.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getReservedQuantity(ProductVariant $variant, ?int $warehouseId = null): int
    {
        if ($variant->is_virtual) {
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
     * Check if variant has sufficient stock.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int|null  $warehouseId
     * @return bool
     */
    public function hasSufficientStock(ProductVariant $variant, int $quantity, ?int $warehouseId = null): bool
    {
        // Virtual stock - always sufficient
        if ($variant->is_virtual) {
            return true;
        }

        // Check min order quantity
        if ($quantity < ($variant->min_order_quantity ?? 1)) {
            return false;
        }

        // Check max order quantity
        if ($variant->max_order_quantity && $quantity > $variant->max_order_quantity) {
            return false;
        }

        // Check available stock
        $available = $this->getAvailableStock($variant, $warehouseId);

        if ($available >= $quantity) {
            return true;
        }

        // Check backorder
        if ($this->canBackorder($variant, $quantity, $available)) {
            return true;
        }

        // Check preorder
        if ($variant->preorder_enabled && $variant->isPreorderAvailable()) {
            return true;
        }

        return false;
    }

    /**
     * Check if backorder is allowed.
     *
     * @param  ProductVariant  $variant
     * @param  int  $requestedQuantity
     * @param  int  $availableQuantity
     * @return bool
     */
    public function canBackorder(ProductVariant $variant, int $requestedQuantity, int $availableQuantity): bool
    {
        $backorderAllowed = $variant->backorder_allowed ?? 'no';

        if ($backorderAllowed === 'no') {
            return false;
        }

        if ($backorderAllowed === 'yes') {
            return true;
        }

        // 'limit' - check backorder limit
        if ($backorderAllowed === 'limit') {
            $backorderLimit = $variant->backorder_limit ?? 0;
            $backorderNeeded = $requestedQuantity - $availableQuantity;
            return $backorderNeeded <= $backorderLimit;
        }

        return false;
    }

    /**
     * Reserve stock.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int|null  $warehouseId
     * @param  string|null  $reservationId
     * @return bool
     */
    public function reserveStock(
        ProductVariant $variant,
        int $quantity,
        ?int $warehouseId = null,
        ?string $reservationId = null
    ): bool {
        if ($variant->is_virtual) {
            return true; // No reservation needed for virtual stock
        }

        return DB::transaction(function () use ($variant, $quantity, $warehouseId, $reservationId) {
            if ($warehouseId) {
                // Reserve from specific warehouse
                $level = InventoryLevel::where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if (!$level) {
                    return false;
                }

                $available = max(0, $level->quantity - $level->reserved_quantity);

                if ($available < $quantity) {
                    // Check if we can backorder
                    if (!$this->canBackorder($variant, $quantity, $available)) {
                        return false;
                    }
                }

                $level->increment('reserved_quantity', $quantity);
                return true;
            }

            // Reserve from multiple warehouses (priority-based)
            $warehouses = $this->getFulfillmentWarehouses($variant, $quantity);
            $remaining = $quantity;

            foreach ($warehouses as $warehouse) {
                $level = InventoryLevel::where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->lockForUpdate()
                    ->first();

                if (!$level) {
                    continue;
                }

                $available = max(0, $level->quantity - $level->reserved_quantity);
                $toReserve = min($remaining, $available);

                if ($toReserve > 0) {
                    $level->increment('reserved_quantity', $toReserve);
                    $remaining -= $toReserve;
                }

                if ($remaining <= 0) {
                    break;
                }
            }

            // Handle backorder for remaining
            if ($remaining > 0 && $this->canBackorder($variant, $quantity, $quantity - $remaining)) {
                return true;
            }

            return $remaining <= 0;
        });
    }

    /**
     * Release reserved stock.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int|null  $warehouseId
     * @return void
     */
    public function releaseStock(ProductVariant $variant, int $quantity, ?int $warehouseId = null): void
    {
        if ($variant->is_virtual) {
            return;
        }

        if ($warehouseId) {
            InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->decrement('reserved_quantity', $quantity);
        } else {
            // Release from all warehouses proportionally
            $levels = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('reserved_quantity', '>', 0)
                ->get();

            foreach ($levels as $level) {
                $toRelease = min($quantity, $level->reserved_quantity);
                $level->decrement('reserved_quantity', $toRelease);
                $quantity -= $toRelease;

                if ($quantity <= 0) {
                    break;
                }
            }
        }
    }

    /**
     * Allocate stock (move from reserved to allocated).
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  int|null  $warehouseId
     * @return bool
     */
    public function allocateStock(ProductVariant $variant, int $quantity, ?int $warehouseId = null): bool
    {
        if ($variant->is_virtual) {
            return true;
        }

        return DB::transaction(function () use ($variant, $quantity, $warehouseId) {
            if ($warehouseId) {
                $level = InventoryLevel::where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if (!$level || $level->reserved_quantity < $quantity) {
                    return false;
                }

                $level->decrement('reserved_quantity', $quantity);
                $level->decrement('quantity', $quantity);
                return true;
            }

            // Allocate from multiple warehouses
            $warehouses = $this->getFulfillmentWarehouses($variant, $quantity);
            $remaining = $quantity;

            foreach ($warehouses as $warehouse) {
                $level = InventoryLevel::where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->lockForUpdate()
                    ->first();

                if (!$level) {
                    continue;
                }

                $toAllocate = min($remaining, $level->reserved_quantity, $level->quantity);

                if ($toAllocate > 0) {
                    $level->decrement('reserved_quantity', $toAllocate);
                    $level->decrement('quantity', $toAllocate);
                    $remaining -= $toAllocate;
                }

                if ($remaining <= 0) {
                    break;
                }
            }

            return $remaining <= 0;
        });
    }

    /**
     * Get stock status.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $warehouseId
     * @return string
     */
    public function getStockStatus(ProductVariant $variant, ?int $warehouseId = null): string
    {
        // Virtual stock
        if ($variant->is_virtual) {
            return 'in_stock';
        }

        // Preorder
        if ($variant->preorder_enabled && $variant->isPreorderAvailable()) {
            return 'preorder';
        }

        $available = $this->getAvailableStock($variant, $warehouseId);
        $threshold = $variant->low_stock_threshold ?? 10;

        if ($available <= 0) {
            // Check backorder
            if ($this->canBackorder($variant, 1, 0)) {
                return 'backorder';
            }
            return 'out_of_stock';
        }

        if ($available <= $threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Update stock status cache.
     *
     * @param  ProductVariant  $variant
     * @return void
     */
    public function updateStockStatus(ProductVariant $variant): void
    {
        $status = $this->getStockStatus($variant);
        $variant->update(['stock_status' => $status]);
    }

    /**
     * Get fulfillment warehouses for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  array  $context
     * @return Collection
     */
    public function getFulfillmentWarehouses(
        ProductVariant $variant,
        int $quantity,
        array $context = []
    ): Collection {
        // Get warehouses with stock
        $warehouses = Warehouse::where('is_active', true)
            ->whereHas('inventoryLevels', function ($q) use ($variant, $quantity) {
                $q->where('product_variant_id', $variant->id)
                  ->whereRaw('(quantity - reserved_quantity) >= ?', [$quantity]);
            })
            ->orderBy('priority')
            ->get();

        // Apply geo-based selection if customer location provided
        if (isset($context['customer_location'])) {
            $warehouses = $this->applyGeoSelection($warehouses, $context['customer_location']);
        }

        // Apply fulfillment rules
        $warehouses = $this->applyFulfillmentRules($warehouses, $variant, $context);

        return $warehouses;
    }

    /**
     * Apply geo-based warehouse selection.
     *
     * @param  Collection  $warehouses
     * @param  array  $customerLocation
     * @return Collection
     */
    protected function applyGeoSelection(Collection $warehouses, array $customerLocation): Collection
    {
        $customerLat = $customerLocation['latitude'] ?? null;
        $customerLng = $customerLocation['longitude'] ?? null;
        $customerCountry = $customerLocation['country'] ?? null;

        if (!$customerLat || !$customerLng) {
            return $warehouses;
        }

        return $warehouses->map(function ($warehouse) use ($customerLat, $customerLng, $customerCountry) {
            // Check service areas
            $serviceAreas = $warehouse->service_areas ?? [];
            
            if (!empty($serviceAreas)) {
                // Check country
                if (isset($serviceAreas['countries']) && !in_array($customerCountry, $serviceAreas['countries'])) {
                    return null;
                }

                // Check regions/postal codes if provided
                if (isset($serviceAreas['regions']) && isset($customerLocation['region'])) {
                    if (!in_array($customerLocation['region'], $serviceAreas['regions'])) {
                        return null;
                    }
                }
            }

            // Calculate distance if warehouse has coordinates
            if ($warehouse->latitude && $warehouse->longitude) {
                $distance = $this->calculateDistance(
                    $customerLat,
                    $customerLng,
                    $warehouse->latitude,
                    $warehouse->longitude
                );
                $warehouse->distance = $distance;
            }

            return $warehouse;
        })->filter()->sortBy('distance');
    }

    /**
     * Calculate distance between two coordinates (Haversine formula).
     *
     * @param  float  $lat1
     * @param  float  $lng1
     * @param  float  $lat2
     * @param  float  $lng2
     * @return float Distance in kilometers
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Apply fulfillment rules.
     *
     * @param  Collection  $warehouses
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return Collection
     */
    protected function applyFulfillmentRules(Collection $warehouses, ProductVariant $variant, array $context): Collection
    {
        // Load fulfillment rules for warehouses
        $warehouses->load('fulfillmentRules');

        return $warehouses->filter(function ($warehouse) use ($variant, $context) {
            $rules = $warehouse->fulfillmentRules ?? collect();

            foreach ($rules as $rule) {
                if (!$rule->is_active) {
                    continue;
                }

                if (!$this->ruleMatches($rule, $variant, $context)) {
                    return false;
                }
            }

            return true;
        })->sortBy('priority');
    }

    /**
     * Check if fulfillment rule matches.
     *
     * @param  object  $rule
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return bool
     */
    protected function ruleMatches(object $rule, ProductVariant $variant, array $context): bool
    {
        $config = $rule->rule_config ?? [];
        $conditions = $rule->conditions ?? [];

        return match($rule->rule_type) {
            'geo_location' => $this->matchesGeoLocation($config, $context),
            'product_type' => $this->matchesProductType($config, $variant),
            'order_value' => $this->matchesOrderValue($config, $context),
            'order_weight' => $this->matchesOrderWeight($config, $variant, $context),
            'customer_group' => $this->matchesCustomerGroup($config, $context),
            'channel' => $this->matchesChannel($config, $context),
            'custom' => $this->matchesCustomRule($config, $variant, $context),
            default => true,
        };
    }

    /**
     * Match geo location rule.
     */
    protected function matchesGeoLocation(array $config, array $context): bool
    {
        // Implementation depends on config structure
        return true;
    }

    /**
     * Match product type rule.
     */
    protected function matchesProductType(array $config, ProductVariant $variant): bool
    {
        $allowedTypes = $config['product_types'] ?? [];
        return empty($allowedTypes) || in_array($variant->product->product_type_id, $allowedTypes);
    }

    /**
     * Match order value rule.
     */
    protected function matchesOrderValue(array $config, array $context): bool
    {
        $orderValue = $context['order_value'] ?? 0;
        $minValue = $config['min_value'] ?? 0;
        $maxValue = $config['max_value'] ?? null;

        if ($orderValue < $minValue) {
            return false;
        }

        if ($maxValue !== null && $orderValue > $maxValue) {
            return false;
        }

        return true;
    }

    /**
     * Match order weight rule.
     */
    protected function matchesOrderWeight(array $config, ProductVariant $variant, array $context): bool
    {
        $weight = $variant->weight ?? 0;
        $minWeight = $config['min_weight'] ?? 0;
        $maxWeight = $config['max_weight'] ?? null;

        if ($weight < $minWeight) {
            return false;
        }

        if ($maxWeight !== null && $weight > $maxWeight) {
            return false;
        }

        return true;
    }

    /**
     * Match customer group rule.
     */
    protected function matchesCustomerGroup(array $config, array $context): bool
    {
        $customerGroupId = $context['customer_group_id'] ?? null;
        $allowedGroups = $config['customer_groups'] ?? [];
        return empty($allowedGroups) || in_array($customerGroupId, $allowedGroups);
    }

    /**
     * Match channel rule.
     */
    protected function matchesChannel(array $config, array $context): bool
    {
        $channelId = $context['channel_id'] ?? null;
        $allowedChannels = $config['channels'] ?? [];
        return empty($allowedChannels) || in_array($channelId, $allowedChannels);
    }

    /**
     * Match custom rule.
     */
    protected function matchesCustomRule(array $config, ProductVariant $variant, array $context): bool
    {
        // Custom rule logic - can be extended
        return true;
    }

    /**
     * Get stock breakdown by warehouse.
     *
     * @param  ProductVariant  $variant
     * @return Collection
     */
    public function getStockBreakdown(ProductVariant $variant): Collection
    {
        if ($variant->is_virtual) {
            return collect([[
                'warehouse_id' => null,
                'warehouse_name' => 'Virtual',
                'quantity' => 999999,
                'reserved_quantity' => 0,
                'available_quantity' => 999999,
                'status' => 'in_stock',
            ]]);
        }

        return InventoryLevel::where('product_variant_id', $variant->id)
            ->with('warehouse')
            ->get()
            ->map(function ($level) {
                return [
                    'warehouse_id' => $level->warehouse_id,
                    'warehouse_name' => $level->warehouse->name ?? 'Unknown',
                    'quantity' => $level->quantity,
                    'reserved_quantity' => $level->reserved_quantity,
                    'available_quantity' => max(0, $level->quantity - $level->reserved_quantity),
                    'incoming_quantity' => $level->incoming_quantity,
                    'status' => $level->status,
                    'is_dropship' => $level->warehouse->is_dropship ?? false,
                ];
            });
    }
}

