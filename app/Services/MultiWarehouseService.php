<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\ChannelWarehouse;
use App\Models\ProductVariant;
use App\Models\InventoryLevel;
use Lunar\Models\Channel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Multi-Warehouse Service.
 * 
 * Handles:
 * - Unlimited warehouses
 * - Stock per variant per warehouse
 * - Warehouse priority
 * - Geo-distance rules
 * - Channel ↔ warehouse mapping
 * - Drop-shipping warehouses
 * - Virtual warehouses (digital goods)
 */
class MultiWarehouseService
{
    /**
     * Get warehouses for a channel, ordered by priority.
     *
     * @param  Channel  $channel
     * @param  bool  $activeOnly
     * @return Collection<Warehouse>
     */
    public function getWarehousesForChannel(Channel $channel, bool $activeOnly = true): Collection
    {
        $query = ChannelWarehouse::where('channel_id', $channel->id)
            ->with('warehouse')
            ->orderedByPriority();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()->pluck('warehouse')->filter();
    }

    /**
     * Get default warehouse for a channel.
     *
     * @param  Channel  $channel
     * @return Warehouse|null
     */
    public function getDefaultWarehouseForChannel(Channel $channel): ?Warehouse
    {
        $mapping = ChannelWarehouse::where('channel_id', $channel->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->with('warehouse')
            ->first();

        return $mapping?->warehouse;
    }

    /**
     * Map channel to warehouse.
     *
     * @param  Channel  $channel
     * @param  Warehouse  $warehouse
     * @param  array  $data
     * @return ChannelWarehouse
     */
    public function mapChannelToWarehouse(
        Channel $channel,
        Warehouse $warehouse,
        array $data = []
    ): ChannelWarehouse {
        // If setting as default, unset other defaults for this channel
        if (($data['is_default'] ?? false) === true) {
            ChannelWarehouse::where('channel_id', $channel->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return ChannelWarehouse::updateOrCreate(
            [
                'channel_id' => $channel->id,
                'warehouse_id' => $warehouse->id,
            ],
            array_merge([
                'priority' => $data['priority'] ?? 0,
                'is_default' => $data['is_default'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'fulfillment_rules' => $data['fulfillment_rules'] ?? null,
            ], $data)
        );
    }

    /**
     * Remove channel-warehouse mapping.
     *
     * @param  Channel  $channel
     * @param  Warehouse  $warehouse
     * @return bool
     */
    public function unmapChannelFromWarehouse(Channel $channel, Warehouse $warehouse): bool
    {
        return ChannelWarehouse::where('channel_id', $channel->id)
            ->where('warehouse_id', $warehouse->id)
            ->delete() > 0;
    }

    /**
     * Get warehouses for variant based on stock availability.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  Channel|null  $channel
     * @param  array  $context
     * @return Collection<Warehouse>
     */
    public function getFulfillmentWarehouses(
        ProductVariant $variant,
        int $quantity,
        ?Channel $channel = null,
        array $context = []
    ): Collection {
        // Start with channel-specific warehouses if channel provided
        if ($channel) {
            $warehouses = $this->getWarehousesForChannel($channel);
        } else {
            // Get all active warehouses ordered by priority
            $warehouses = Warehouse::active()
                ->orderedByPriority()
                ->get();
        }

        // Filter by stock availability
        $warehouses = $warehouses->filter(function ($warehouse) use ($variant, $quantity) {
            // Virtual warehouses always available
            if ($warehouse->is_virtual) {
                return true;
            }

            // Check inventory level
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();

            if (!$level) {
                return false;
            }

            // Check if warehouse has sufficient stock
            return $level->available_quantity >= $quantity;
        });

        // Apply geo-distance rules if customer location provided
        if (isset($context['customer_location'])) {
            $warehouses = $this->applyGeoDistanceRules($warehouses, $context['customer_location']);
        }

        // Apply warehouse-specific fulfillment rules
        $warehouses = $this->applyWarehouseFulfillmentRules($warehouses, $variant, $context);

        return $warehouses->sortBy('priority');
    }

    /**
     * Apply geo-distance rules to warehouses.
     *
     * @param  Collection<Warehouse>  $warehouses
     * @param  array  $customerLocation
     * @return Collection<Warehouse>
     */
    public function applyGeoDistanceRules(Collection $warehouses, array $customerLocation): Collection
    {
        $customerLat = $customerLocation['latitude'] ?? null;
        $customerLng = $customerLocation['longitude'] ?? null;

        if (!$customerLat || !$customerLng) {
            return $warehouses;
        }

        return $warehouses->map(function ($warehouse) use ($customerLat, $customerLng) {
            // Check if warehouse serves this location
            if (!$warehouse->servesLocation($customerLocation)) {
                return null;
            }

            // Calculate distance
            $distance = $warehouse->distanceTo($customerLat, $customerLng);

            if ($distance === null) {
                return $warehouse; // No coordinates, can't calculate distance
            }

            // Check max fulfillment distance
            if ($warehouse->max_fulfillment_distance && $distance > $warehouse->max_fulfillment_distance) {
                return null;
            }

            // Apply geo-distance rules
            $rules = $warehouse->geo_distance_rules ?? [];
            if (!empty($rules)) {
                if (!$this->matchesGeoDistanceRules($rules, $distance, $customerLocation)) {
                    return null;
                }
            }

            $warehouse->distance = $distance;
            return $warehouse;
        })->filter()->sortBy('distance');
    }

    /**
     * Check if warehouse matches geo-distance rules.
     *
     * @param  array  $rules
     * @param  float  $distance
     * @param  array  $customerLocation
     * @return bool
     */
    protected function matchesGeoDistanceRules(array $rules, float $distance, array $customerLocation): bool
    {
        foreach ($rules as $rule) {
            $ruleType = $rule['type'] ?? null;
            $ruleValue = $rule['value'] ?? null;

            switch ($ruleType) {
                case 'max_distance':
                    if ($distance > $ruleValue) {
                        return false;
                    }
                    break;

                case 'min_distance':
                    if ($distance < $ruleValue) {
                        return false;
                    }
                    break;

                case 'distance_range':
                    $min = $rule['min'] ?? 0;
                    $max = $rule['max'] ?? PHP_INT_MAX;
                    if ($distance < $min || $distance > $max) {
                        return false;
                    }
                    break;

                case 'country_priority':
                    $priorityCountries = $rule['countries'] ?? [];
                    if (!empty($priorityCountries) && isset($customerLocation['country'])) {
                        if (in_array($customerLocation['country'], $priorityCountries)) {
                            return true; // Priority country, always allow
                        }
                    }
                    break;

                case 'region_priority':
                    $priorityRegions = $rule['regions'] ?? [];
                    if (!empty($priorityRegions) && isset($customerLocation['region'])) {
                        if (in_array($customerLocation['region'], $priorityRegions)) {
                            return true; // Priority region, always allow
                        }
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Apply warehouse-specific fulfillment rules.
     *
     * @param  Collection<Warehouse>  $warehouses
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return Collection<Warehouse>
     */
    public function applyWarehouseFulfillmentRules(
        Collection $warehouses,
        ProductVariant $variant,
        array $context
    ): Collection {
        return $warehouses->filter(function ($warehouse) use ($variant, $context) {
            // Load fulfillment rules
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
        });
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
        $conditions = $rule->conditions ?? [];

        return match($rule->rule_type ?? 'default') {
            'product_type' => $this->matchesProductType($conditions, $variant),
            'order_value' => $this->matchesOrderValue($conditions, $context),
            'order_weight' => $this->matchesOrderWeight($conditions, $variant, $context),
            'customer_group' => $this->matchesCustomerGroup($conditions, $context),
            'channel' => $this->matchesChannel($conditions, $context),
            'geo_location' => $this->matchesGeoLocation($conditions, $context),
            default => true,
        };
    }

    /**
     * Match product type rule.
     */
    protected function matchesProductType(array $conditions, ProductVariant $variant): bool
    {
        $allowedTypes = $conditions['product_types'] ?? [];
        return empty($allowedTypes) || in_array($variant->product->product_type_id ?? null, $allowedTypes);
    }

    /**
     * Match order value rule.
     */
    protected function matchesOrderValue(array $conditions, array $context): bool
    {
        $orderValue = $context['order_value'] ?? 0;
        $minValue = $conditions['min_value'] ?? 0;
        $maxValue = $conditions['max_value'] ?? null;

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
    protected function matchesOrderWeight(array $conditions, ProductVariant $variant, array $context): bool
    {
        $weight = $variant->weight ?? 0;
        $minWeight = $conditions['min_weight'] ?? 0;
        $maxWeight = $conditions['max_weight'] ?? null;

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
    protected function matchesCustomerGroup(array $conditions, array $context): bool
    {
        $customerGroupId = $context['customer_group_id'] ?? null;
        $allowedGroups = $conditions['customer_groups'] ?? [];
        return empty($allowedGroups) || in_array($customerGroupId, $allowedGroups);
    }

    /**
     * Match channel rule.
     */
    protected function matchesChannel(array $conditions, array $context): bool
    {
        $channelId = $context['channel_id'] ?? null;
        $allowedChannels = $conditions['channels'] ?? [];
        return empty($allowedChannels) || in_array($channelId, $allowedChannels);
    }

    /**
     * Match geo location rule.
     */
    protected function matchesGeoLocation(array $conditions, array $context): bool
    {
        $customerLocation = $context['customer_location'] ?? [];
        $allowedCountries = $conditions['countries'] ?? [];
        $allowedRegions = $conditions['regions'] ?? [];

        if (!empty($allowedCountries) && isset($customerLocation['country'])) {
            if (!in_array($customerLocation['country'], $allowedCountries)) {
                return false;
            }
        }

        if (!empty($allowedRegions) && isset($customerLocation['region'])) {
            if (!in_array($customerLocation['region'], $allowedRegions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get stock breakdown by warehouse for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Channel|null  $channel
     * @return Collection
     */
    public function getStockBreakdownByWarehouse(
        ProductVariant $variant,
        ?Channel $channel = null
    ): Collection {
        // Get warehouses (channel-specific or all)
        if ($channel) {
            $warehouses = $this->getWarehousesForChannel($channel);
        } else {
            $warehouses = Warehouse::active()->orderedByPriority()->get();
        }

        return $warehouses->map(function ($warehouse) use ($variant) {
            $level = InventoryLevel::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();

            return [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'warehouse_code' => $warehouse->code,
                'priority' => $warehouse->priority,
                'is_virtual' => $warehouse->is_virtual ?? false,
                'is_dropship' => $warehouse->is_dropship ?? false,
                'on_hand_quantity' => $level?->quantity ?? 0,
                'reserved_quantity' => $level?->reserved_quantity ?? 0,
                'available_quantity' => $level?->available_quantity ?? 0,
                'incoming_quantity' => $level?->incoming_quantity ?? 0,
                'damaged_quantity' => $level?->damaged_quantity ?? 0,
                'preorder_quantity' => $level?->preorder_quantity ?? 0,
                'status' => $level?->status ?? 'out_of_stock',
            ];
        });
    }

    /**
     * Create virtual warehouse for digital goods.
     *
     * @param  array  $data
     * @return Warehouse
     */
    public function createVirtualWarehouse(array $data): Warehouse
    {
        return Warehouse::create(array_merge($data, [
            'is_virtual' => true,
            'is_active' => true,
            'priority' => $data['priority'] ?? 9999, // Lower priority for virtual
        ]));
    }

    /**
     * Check if warehouse is virtual.
     *
     * @param  Warehouse  $warehouse
     * @return bool
     */
    public function isVirtualWarehouse(Warehouse $warehouse): bool
    {
        return $warehouse->is_virtual ?? false;
    }

    /**
     * Check if warehouse is drop-shipping.
     *
     * @param  Warehouse  $warehouse
     * @return bool
     */
    public function isDropshipWarehouse(Warehouse $warehouse): bool
    {
        return $warehouse->is_dropship ?? false;
    }
}


