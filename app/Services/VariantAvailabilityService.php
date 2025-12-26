<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantAvailabilityRestriction;
use App\Services\VariantInventoryEngine;
use App\Services\MultiWarehouseService;
use Lunar\Models\Channel;
use Lunar\Models\CustomerGroup;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Variant Availability Service.
 * 
 * Handles:
 * - Hard stop vs soft stop (backorders)
 * - Country-based availability
 * - Channel-based availability
 * - Customer-group restrictions
 * - Lead time calculation
 * - "Only X left" logic
 */
class VariantAvailabilityService
{
    protected VariantInventoryEngine $inventoryEngine;
    protected MultiWarehouseService $warehouseService;

    /**
     * Low stock thresholds for "Only X left" messaging.
     */
    protected array $lowStockThresholds = [
        1 => 'critical',
        5 => 'very_low',
        10 => 'low',
    ];

    public function __construct(
        VariantInventoryEngine $inventoryEngine,
        MultiWarehouseService $warehouseService
    ) {
        $this->inventoryEngine = $inventoryEngine;
        $this->warehouseService = $warehouseService;
    }

    /**
     * Check variant availability with all restrictions.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  array  $context
     * @return array
     */
    public function checkAvailability(
        ProductVariant $variant,
        int $quantity = 1,
        array $context = []
    ): array {
        // Get context
        $channel = $context['channel'] ?? null;
        $customerGroup = $context['customer_group'] ?? null;
        $country = $context['country'] ?? null;
        $warehouseId = $context['warehouse_id'] ?? null;

        // Check restrictions first (hard stops)
        $restrictionCheck = $this->checkRestrictions($variant, $channel, $customerGroup, $country);
        if (!$restrictionCheck['available']) {
            return [
                'available' => false,
                'type' => 'hard_stop',
                'reason' => $restrictionCheck['reason'],
                'can_backorder' => false,
                'lead_time' => null,
                'available_quantity' => 0,
                'stock_status' => 'unavailable',
            ];
        }

        // Get inventory summary
        $inventory = $this->inventoryEngine->getInventorySummary($variant, $warehouseId);
        $availableQuantity = $inventory['available_quantity'];

        // Check if sufficient stock available
        if ($availableQuantity >= $quantity) {
            return $this->buildAvailableResponse($variant, $quantity, $availableQuantity, $inventory, $context);
        }

        // Insufficient stock - check backorder (soft stop)
        $backorderCheck = $this->checkBackorder($variant, $quantity, $availableQuantity, $warehouseId);
        if ($backorderCheck['allowed']) {
            return $this->buildBackorderResponse($variant, $quantity, $availableQuantity, $backorderCheck, $inventory, $context);
        }

        // Hard stop - no stock and no backorder
        return [
            'available' => false,
            'type' => 'hard_stop',
            'reason' => 'Out of stock and backorder not allowed',
            'can_backorder' => false,
            'lead_time' => null,
            'available_quantity' => $availableQuantity,
            'stock_status' => 'out_of_stock',
            'only_x_left' => $this->getOnlyXLeftMessage($availableQuantity),
        ];
    }

    /**
     * Check availability restrictions.
     *
     * @param  ProductVariant  $variant
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @param  string|null  $country
     * @return array
     */
    public function checkRestrictions(
        ProductVariant $variant,
        ?Channel $channel = null,
        ?CustomerGroup $customerGroup = null,
        ?string $country = null
    ): array {
        $restrictions = VariantAvailabilityRestriction::where('product_variant_id', $variant->id)
            ->active()
            ->orderedByPriority()
            ->get();

        // Check country restrictions
        if ($country) {
            $countryRestriction = $restrictions
                ->where('restriction_type', 'country')
                ->where('restriction_value', $country)
                ->first();

            if ($countryRestriction) {
                if ($countryRestriction->action === 'deny') {
                    return [
                        'available' => false,
                        'reason' => $countryRestriction->reason ?? "Product not available in {$country}",
                    ];
                }
            }
        }

        // Check channel restrictions
        if ($channel) {
            $channelRestriction = $restrictions
                ->where('restriction_type', 'channel')
                ->where('restriction_value', (string) $channel->id)
                ->first();

            if ($channelRestriction) {
                if ($channelRestriction->action === 'deny') {
                    return [
                        'available' => false,
                        'reason' => $channelRestriction->reason ?? "Product not available in this channel",
                    ];
                }
            }
        }

        // Check customer group restrictions
        if ($customerGroup) {
            $customerGroupRestriction = $restrictions
                ->where('restriction_type', 'customer_group')
                ->where('restriction_value', (string) $customerGroup->id)
                ->first();

            if ($customerGroupRestriction) {
                if ($customerGroupRestriction->action === 'deny') {
                    return [
                        'available' => false,
                        'reason' => $customerGroupRestriction->reason ?? "Product not available for your customer group",
                    ];
                }
            }
        }

        return ['available' => true];
    }

    /**
     * Check backorder availability (soft stop).
     *
     * @param  ProductVariant  $variant
     * @param  int  $requestedQuantity
     * @param  int  $availableQuantity
     * @param  int|null  $warehouseId
     * @return array
     */
    public function checkBackorder(
        ProductVariant $variant,
        int $requestedQuantity,
        int $availableQuantity,
        ?int $warehouseId = null
    ): array {
        $backorderAllowed = $variant->backorder_allowed ?? false;

        if (!$backorderAllowed) {
            return [
                'allowed' => false,
                'reason' => 'Backorder not allowed',
            ];
        }

        $backorderLimit = $this->inventoryEngine->getBackorderLimit($variant, $warehouseId);
        $backorderNeeded = max(0, $requestedQuantity - $availableQuantity);

        if ($backorderLimit !== null && $backorderNeeded > $backorderLimit) {
            return [
                'allowed' => false,
                'reason' => "Backorder limit exceeded. Maximum backorder: {$backorderLimit}",
                'backorder_limit' => $backorderLimit,
                'backorder_needed' => $backorderNeeded,
            ];
        }

        return [
            'allowed' => true,
            'backorder_quantity' => $backorderNeeded,
            'backorder_limit' => $backorderLimit,
        ];
    }

    /**
     * Calculate lead time.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  array  $context
     * @return array
     */
    public function calculateLeadTime(
        ProductVariant $variant,
        int $quantity,
        array $context = []
    ): array {
        $leadTimeDays = $variant->lead_time_days ?? 0;
        $warehouseId = $context['warehouse_id'] ?? null;
        $country = $context['country'] ?? null;

        // Get warehouse lead time if available
        if ($warehouseId) {
            $warehouse = \App\Models\Warehouse::find($warehouseId);
            if ($warehouse && isset($warehouse->fulfillment_rules['lead_time_days'])) {
                $leadTimeDays = max($leadTimeDays, $warehouse->fulfillment_rules['lead_time_days']);
            }
        }

        // Check if backorder is needed
        $inventory = $this->inventoryEngine->getInventorySummary($variant, $warehouseId);
        $availableQuantity = $inventory['available_quantity'];

        if ($quantity > $availableQuantity) {
            // Backorder - add additional lead time
            $backorderLeadTime = $variant->backorder_lead_time_days ?? 7;
            $leadTimeDays += $backorderLeadTime;
        }

        // Calculate estimated ship date
        $estimatedShipDate = now()->addDays($leadTimeDays);

        return [
            'lead_time_days' => $leadTimeDays,
            'estimated_ship_date' => $estimatedShipDate->toDateString(),
            'estimated_ship_datetime' => $estimatedShipDate->toIso8601String(),
            'is_backorder' => $quantity > $availableQuantity,
            'available_quantity' => $availableQuantity,
        ];
    }

    /**
     * Get "Only X left" message.
     *
     * @param  int  $availableQuantity
     * @return array|null
     */
    public function getOnlyXLeftMessage(int $availableQuantity): ?array
    {
        if ($availableQuantity <= 0) {
            return null;
        }

        $threshold = $this->getLowStockThreshold($availableQuantity);

        if (!$threshold) {
            return null;
        }

        return [
            'quantity' => $availableQuantity,
            'message' => $this->formatOnlyXLeftMessage($availableQuantity, $threshold),
            'severity' => $threshold,
            'show_warning' => true,
        ];
    }

    /**
     * Get low stock threshold for quantity.
     *
     * @param  int  $quantity
     * @return string|null
     */
    protected function getLowStockThreshold(int $quantity): ?string
    {
        foreach ($this->lowStockThresholds as $threshold => $severity) {
            if ($quantity <= $threshold) {
                return $severity;
            }
        }

        return null;
    }

    /**
     * Format "Only X left" message.
     *
     * @param  int  $quantity
     * @param  string  $severity
     * @return string
     */
    protected function formatOnlyXLeftMessage(int $quantity, string $severity): string
    {
        return match($severity) {
            'critical' => "Only {$quantity} left!",
            'very_low' => "Only {$quantity} left in stock",
            'low' => "Only {$quantity} left",
            default => "Low stock",
        };
    }

    /**
     * Build available response.
     *
     * @param  ProductVariant  $variant
     * @param  int  $requestedQuantity
     * @param  int  $availableQuantity
     * @param  array  $inventory
     * @param  array  $context
     * @return array
     */
    protected function buildAvailableResponse(
        ProductVariant $variant,
        int $requestedQuantity,
        int $availableQuantity,
        array $inventory,
        array $context
    ): array {
        $leadTime = $this->calculateLeadTime($variant, $requestedQuantity, $context);

        return [
            'available' => true,
            'type' => 'in_stock',
            'reason' => 'In stock',
            'can_backorder' => false,
            'lead_time' => $leadTime,
            'available_quantity' => $availableQuantity,
            'stock_status' => $this->getStockStatus($availableQuantity, $variant),
            'only_x_left' => $this->getOnlyXLeftMessage($availableQuantity),
            'inventory' => $inventory,
        ];
    }

    /**
     * Build backorder response.
     *
     * @param  ProductVariant  $variant
     * @param  int  $requestedQuantity
     * @param  int  $availableQuantity
     * @param  array  $backorderCheck
     * @param  array  $inventory
     * @param  array  $context
     * @return array
     */
    protected function buildBackorderResponse(
        ProductVariant $variant,
        int $requestedQuantity,
        int $availableQuantity,
        array $backorderCheck,
        array $inventory,
        array $context
    ): array {
        $leadTime = $this->calculateLeadTime($variant, $requestedQuantity, $context);

        return [
            'available' => true,
            'type' => 'backorder',
            'reason' => 'Available on backorder',
            'can_backorder' => true,
            'lead_time' => $leadTime,
            'available_quantity' => $availableQuantity,
            'backorder_quantity' => $backorderCheck['backorder_quantity'],
            'backorder_limit' => $backorderCheck['backorder_limit'],
            'stock_status' => 'backorder',
            'only_x_left' => $availableQuantity > 0 ? $this->getOnlyXLeftMessage($availableQuantity) : null,
            'inventory' => $inventory,
        ];
    }

    /**
     * Get stock status.
     *
     * @param  int  $availableQuantity
     * @param  ProductVariant  $variant
     * @return string
     */
    protected function getStockStatus(int $availableQuantity, ProductVariant $variant): string
    {
        if ($availableQuantity <= 0) {
            if ($variant->backorder_allowed) {
                return 'backorder';
            }
            return 'out_of_stock';
        }

        $threshold = $variant->low_stock_threshold ?? 10;
        if ($availableQuantity <= $threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Check country-based availability.
     *
     * @param  ProductVariant  $variant
     * @param  string  $countryCode
     * @return bool
     */
    public function isAvailableInCountry(ProductVariant $variant, string $countryCode): bool
    {
        $restriction = VariantAvailabilityRestriction::where('product_variant_id', $variant->id)
            ->where('restriction_type', 'country')
            ->where('restriction_value', $countryCode)
            ->where('action', 'deny')
            ->active()
            ->exists();

        return !$restriction;
    }

    /**
     * Check channel-based availability.
     *
     * @param  ProductVariant  $variant
     * @param  Channel  $channel
     * @return bool
     */
    public function isAvailableInChannel(ProductVariant $variant, Channel $channel): bool
    {
        $restriction = VariantAvailabilityRestriction::where('product_variant_id', $variant->id)
            ->where('restriction_type', 'channel')
            ->where('restriction_value', (string) $channel->id)
            ->where('action', 'deny')
            ->active()
            ->exists();

        return !$restriction;
    }

    /**
     * Check customer-group availability.
     *
     * @param  ProductVariant  $variant
     * @param  CustomerGroup  $customerGroup
     * @return bool
     */
    public function isAvailableForCustomerGroup(ProductVariant $variant, CustomerGroup $customerGroup): bool
    {
        $restriction = VariantAvailabilityRestriction::where('product_variant_id', $variant->id)
            ->where('restriction_type', 'customer_group')
            ->where('restriction_value', (string) $customerGroup->id)
            ->where('action', 'deny')
            ->active()
            ->exists();

        return !$restriction;
    }

    /**
     * Add availability restriction.
     *
     * @param  ProductVariant  $variant
     * @param  string  $type
     * @param  string  $value
     * @param  string  $action
     * @param  string|null  $reason
     * @param  int  $priority
     * @return VariantAvailabilityRestriction
     */
    public function addRestriction(
        ProductVariant $variant,
        string $type,
        string $value,
        string $action = 'deny',
        ?string $reason = null,
        int $priority = 0
    ): VariantAvailabilityRestriction {
        return VariantAvailabilityRestriction::updateOrCreate(
            [
                'product_variant_id' => $variant->id,
                'restriction_type' => $type,
                'restriction_value' => $value,
            ],
            [
                'action' => $action,
                'reason' => $reason,
                'priority' => $priority,
                'is_active' => true,
            ]
        );
    }

    /**
     * Remove availability restriction.
     *
     * @param  ProductVariant  $variant
     * @param  string  $type
     * @param  string  $value
     * @return bool
     */
    public function removeRestriction(
        ProductVariant $variant,
        string $type,
        string $value
    ): bool {
        return VariantAvailabilityRestriction::where('product_variant_id', $variant->id)
            ->where('restriction_type', $type)
            ->where('restriction_value', $value)
            ->delete() > 0;
    }

    /**
     * Get availability summary.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return array
     */
    public function getAvailabilitySummary(ProductVariant $variant, array $context = []): array
    {
        $availability = $this->checkAvailability($variant, 1, $context);
        $inventory = $this->inventoryEngine->getInventorySummary($variant, $context['warehouse_id'] ?? null);
        $leadTime = $this->calculateLeadTime($variant, 1, $context);

        return [
            'available' => $availability['available'],
            'type' => $availability['type'] ?? 'unknown',
            'stock_status' => $availability['stock_status'] ?? 'unknown',
            'available_quantity' => $availability['available_quantity'] ?? 0,
            'can_backorder' => $availability['can_backorder'] ?? false,
            'lead_time' => $leadTime,
            'only_x_left' => $availability['only_x_left'] ?? null,
            'restrictions' => [
                'country' => $context['country'] ? !$this->isAvailableInCountry($variant, $context['country']) : null,
                'channel' => $context['channel'] ? !$this->isAvailableInChannel($variant, $context['channel']) : null,
                'customer_group' => $context['customer_group'] ? !$this->isAvailableForCustomerGroup($variant, $context['customer_group']) : null,
            ],
            'inventory' => $inventory,
        ];
    }
}


