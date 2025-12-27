<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantPrice;
use App\Models\PriceSimulation;
use App\Models\MarginAlert;
use App\Models\PriceHistory;
use App\Services\PriorityPricingResolver;
use App\Services\PricingRuleEngine;
use Lunar\Models\Currency;
use Lunar\Models\Channel;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Customer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Advanced Pricing Service.
 * 
 * Handles:
 * - Time windows (sales, flash deals)
 * - Price locks (cannot be discounted)
 * - Scheduled price changes
 * - Dynamic pricing hooks (ERP, AI, scripts)
 * - Price simulation (preview final price)
 * - Margin alerts
 * - Historical price tracking (legal compliance)
 */
class AdvancedPricingService
{
    protected PriorityPricingResolver $resolver;
    protected PricingRuleEngine $ruleEngine;

    public function __construct(PriorityPricingResolver $resolver, PricingRuleEngine $ruleEngine)
    {
        $this->resolver = $resolver;
        $this->ruleEngine = $ruleEngine;
    }

    /**
     * Calculate a price for a variant for use in storefront/cart pricing.
     *
     * This is a lightweight wrapper around the PriorityPricingResolver.
     * It does NOT create simulation or history records.
     *
     * @return array{
     *   price:int,
     *   original_price?:int,
     *   compare_at_price?:int|null,
     *   layer?:string|null,
     *   source?:string|null,
     *   currency?:\Lunar\Models\Currency|null,
     *   tax_inclusive?:bool,
     *   applied_rules?:\Illuminate\Support\Collection
     * }
     */
    public function calculatePrice(
        ProductVariant $variant,
        int $quantity = 1,
        ?Currency $currency = null,
        ?CustomerGroup $customerGroup = null,
        ?Channel $channel = null,
        bool $includeTax = false,
        ?Customer $customer = null
    ): array {
        $priceData = $this->resolver->resolvePrice(
            $variant,
            $quantity,
            $currency,
            $channel,
            $customerGroup,
            $customer
        );

        if (!$priceData) {
            return [
                'price' => 0,
                'original_price' => 0,
                'compare_at_price' => null,
                'layer' => null,
                'source' => null,
                'currency' => $currency,
                'tax_inclusive' => $includeTax,
                'applied_rules' => collect(),
            ];
        }

        $priceData['tax_inclusive'] = $includeTax;

        return $priceData;
    }

    /**
     * Create a time-windowed price (sale, flash deal).
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return VariantPrice
     */
    public function createTimeWindowPrice(ProductVariant $variant, array $data): VariantPrice
    {
        return VariantPrice::create([
            'variant_id' => $variant->id,
            'currency_id' => $data['currency_id'],
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'channel_id' => $data['channel_id'] ?? null,
            'customer_group_id' => $data['customer_group_id'] ?? null,
            'pricing_layer' => 'promotional',
            'starts_at' => $data['starts_at'] ?? now(),
            'ends_at' => $data['ends_at'] ?? null,
            'is_flash_deal' => $data['is_flash_deal'] ?? false,
            'priority' => $data['priority'] ?? 600,
            'is_active' => true,
        ]);
    }

    /**
     * Create a flash deal (short-term sale).
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return VariantPrice
     */
    public function createFlashDeal(ProductVariant $variant, array $data): VariantPrice
    {
        $data['is_flash_deal'] = true;
        $data['pricing_layer'] = 'promotional';
        $data['priority'] = $data['priority'] ?? 700; // Higher priority for flash deals
        
        return $this->createTimeWindowPrice($variant, $data);
    }

    /**
     * Schedule a price change.
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return VariantPrice
     */
    public function schedulePriceChange(ProductVariant $variant, array $data): VariantPrice
    {
        $price = VariantPrice::create([
            'variant_id' => $variant->id,
            'currency_id' => $data['currency_id'],
            'price' => $data['current_price'] ?? $variant->prices()->first()?->price ?? 0,
            'scheduled_price' => $data['scheduled_price'],
            'scheduled_change_at' => $data['scheduled_change_at'],
            'channel_id' => $data['channel_id'] ?? null,
            'customer_group_id' => $data['customer_group_id'] ?? null,
            'pricing_layer' => $data['pricing_layer'] ?? 'base',
            'is_active' => false, // Inactive until scheduled time
        ]);

        // Track in history
        $this->trackPriceChange($variant, [
            'price' => $data['current_price'] ?? $variant->prices()->first()?->price ?? 0,
            'scheduled_price' => $data['scheduled_price'],
            'scheduled_change_at' => $data['scheduled_change_at'],
            'change_reason' => 'scheduled',
        ]);

        return $price;
    }

    /**
     * Process scheduled price changes.
     *
     * @return int Number of prices updated
     */
    public function processScheduledPriceChanges(): int
    {
        $scheduledPrices = VariantPrice::whereNotNull('scheduled_change_at')
            ->where('scheduled_change_at', '<=', now())
            ->where('is_active', false)
            ->get();

        $updated = 0;

        foreach ($scheduledPrices as $price) {
            DB::transaction(function () use ($price, &$updated) {
                // Record old price in history
                $oldPrice = $price->price;
                
                // Update to scheduled price
                $price->update([
                    'price' => $price->scheduled_price,
                    'scheduled_price' => null,
                    'scheduled_change_at' => null,
                    'is_active' => true,
                ]);

                // Track in history
                $this->trackPriceChange($price->variant, [
                    'price' => $price->scheduled_price,
                    'previous_price' => $oldPrice,
                    'change_reason' => 'scheduled_executed',
                    'changed_by' => null,
                ]);

                $updated++;
            });
        }

        return $updated;
    }

    /**
     * Lock price (prevent discounts).
     *
     * @param  ProductVariant  $variant
     * @param  string|null  $reason
     * @return bool
     */
    public function lockPrice(ProductVariant $variant, ?string $reason = null): bool
    {
        return $variant->update([
            'price_locked' => true,
        ]);
    }

    /**
     * Unlock price.
     *
     * @param  ProductVariant  $variant
     * @return bool
     */
    public function unlockPrice(ProductVariant $variant): bool
    {
        return $variant->update([
            'price_locked' => false,
        ]);
    }

    /**
     * Execute dynamic pricing hook.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  Currency  $currency
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @return int|null
     */
    public function executeDynamicPricingHook(
        ProductVariant $variant,
        int $quantity,
        Currency $currency,
        ?Channel $channel = null,
        ?CustomerGroup $customerGroup = null
    ): ?int {
        $hook = \App\Models\VariantPriceHook::where('product_variant_id', $variant->id)
            ->where('is_active', true)
            ->orderBy('priority')
            ->first();

        if (!$hook) {
            return null;
        }

        // Check cache
        if ($hook->cached_price && $hook->cached_at) {
            $cacheAge = now()->diffInMinutes($hook->cached_at);
            if ($cacheAge < $hook->cache_ttl_minutes) {
                return $hook->cached_price;
            }
        }

        // Execute hook service
        $hookService = $hook->hook_service;
        
        if (!class_exists($hookService)) {
            \Log::warning("Dynamic pricing hook service not found: {$hookService}");
            return null;
        }

        try {
            $service = app($hookService);
            
            if (method_exists($service, 'calculatePrice')) {
                $price = $service->calculatePrice($variant, $quantity, $currency, $channel, $customerGroup);
                
                // Cache result
                $hook->update([
                    'cached_price' => $price,
                    'cached_at' => now(),
                ]);

                return $price;
            }
        } catch (\Exception $e) {
            \Log::error("Dynamic pricing hook error: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Simulate price (preview final price).
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  Currency|null  $currency
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @param  Customer|null  $customer
     * @param  array  $context
     * @return array
     */
    public function simulatePrice(
        ProductVariant $variant,
        int $quantity = 1,
        ?Currency $currency = null,
        ?Channel $channel = null,
        ?CustomerGroup $customerGroup = null,
        ?Customer $customer = null,
        array $context = []
    ): array {
        // Resolve base price
        $priceData = $this->resolver->resolvePrice(
            $variant,
            $quantity,
            $currency,
            $channel,
            $customerGroup,
            $customer
        );

        if (!$priceData) {
            throw new \RuntimeException('No price found for variant.');
        }

        $basePrice = $priceData['price'];

        // Apply pricing rules
        $ruleResult = $this->ruleEngine->applyRules(
            $variant,
            $basePrice,
            $quantity,
            $currency,
            $channel,
            $customerGroup,
            $customer,
            $context
        );

        // Calculate margin
        $margin = $this->calculateMargin($variant, $ruleResult['final_price']);

        // Store simulation
        PriceSimulation::create([
            'product_variant_id' => $variant->id,
            'currency_id' => $currency?->id ?? Currency::default()->first()?->id,
            'quantity' => $quantity,
            'channel_id' => $channel?->id,
            'customer_group_id' => $customerGroup?->id,
            'customer_id' => $customer?->id,
            'base_price' => $basePrice,
            'final_price' => $ruleResult['final_price'],
            'applied_rules' => $ruleResult['applied_rules']->toArray(),
            'pricing_breakdown' => [
                'base_price' => $basePrice,
                'layer' => $priceData['layer'] ?? null,
                'source' => $priceData['source'] ?? null,
                'rules_applied' => $ruleResult['applied_rules']->count(),
                'total_discount' => $ruleResult['total_discount'],
                'final_price' => $ruleResult['final_price'],
                'margin_percentage' => $margin['percentage'],
                'margin_amount' => $margin['amount'],
            ],
            'simulation_context' => json_encode($context),
        ]);

        return [
            'base_price' => $basePrice,
            'final_price' => $ruleResult['final_price'],
            'total_discount' => $ruleResult['total_discount'],
            'applied_rules' => $ruleResult['applied_rules'],
            'pricing_layer' => $priceData['layer'] ?? null,
            'margin' => $margin,
            'breakdown' => [
                'base_price' => $basePrice,
                'discounts' => $ruleResult['applied_rules']->sum('adjustment'),
                'final_price' => $ruleResult['final_price'],
            ],
        ];
    }

    /**
     * Check and create margin alerts.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $price
     * @param  float|null  $thresholdMargin
     * @return MarginAlert|null
     */
    public function checkMarginAlert(ProductVariant $variant, ?int $price = null, ?float $thresholdMargin = null): ?MarginAlert
    {
        $costPrice = $variant->cost_price;
        
        if ($costPrice === null || $costPrice <= 0) {
            return null; // No cost price, can't calculate margin
        }

        if ($price === null) {
            $currency = Currency::default()->first();
            $priceData = $this->resolver->resolvePrice($variant, 1, $currency);
            $price = $priceData['price'] ?? 0;
        }

        if ($price <= 0) {
            return null;
        }

        $marginAmount = $price - $costPrice;
        $marginPercentage = ($marginAmount / $price) * 100;

        $thresholdMargin = $thresholdMargin ?? config('lunar.pricing.margin_alert_threshold', 10.0);

        // Check for negative margin
        if ($marginPercentage < 0) {
            return MarginAlert::create([
                'product_variant_id' => $variant->id,
                'alert_type' => 'negative_margin',
                'current_margin_percentage' => $marginPercentage,
                'current_price' => $price,
                'cost_price' => $costPrice,
                'message' => "Variant has negative margin: {$marginPercentage}%",
            ]);
        }

        // Check for low margin
        if ($marginPercentage < $thresholdMargin) {
            return MarginAlert::create([
                'product_variant_id' => $variant->id,
                'alert_type' => 'low_margin',
                'current_margin_percentage' => $marginPercentage,
                'threshold_margin_percentage' => $thresholdMargin,
                'current_price' => $price,
                'cost_price' => $costPrice,
                'message' => "Variant margin ({$marginPercentage}%) below threshold ({$thresholdMargin}%)",
            ]);
        }

        return null;
    }

    /**
     * Track price change in history.
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return PriceHistory
     */
    public function trackPriceChange(ProductVariant $variant, array $data): PriceHistory
    {
        $currency = $data['currency'] ?? Currency::default()->first();
        
        if (!$currency) {
            throw new \RuntimeException('No currency available for price tracking.');
        }

        // Get current price
        $currentPrice = $variant->prices()
            ->where('currency_id', $currency->id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->first();

        // Close previous price history entry
        PriceHistory::where('product_variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->whereNull('effective_to')
            ->update(['effective_to' => now()]);

        // Create new history entry
        return PriceHistory::create([
            'product_variant_id' => $variant->id,
            'currency_id' => $currency->id,
            'price' => $data['price'] ?? $currentPrice?->price ?? 0,
            'compare_at_price' => $data['compare_at_price'] ?? $currentPrice?->compare_at_price ?? null,
            'channel_id' => $data['channel_id'] ?? $currentPrice?->channel_id,
            'customer_group_id' => $data['customer_group_id'] ?? $currentPrice?->customer_group_id,
            'pricing_layer' => $data['pricing_layer'] ?? $currentPrice?->pricing_layer ?? 'base',
            'pricing_rule_id' => $data['pricing_rule_id'] ?? null,
            'changed_by' => $data['changed_by'] ?? auth()->id(),
            'change_reason' => $data['change_reason'] ?? 'manual',
            'change_metadata' => $data['change_metadata'] ?? null,
            'effective_from' => $data['effective_from'] ?? now(),
            'effective_to' => $data['effective_to'] ?? null,
        ]);
    }

    /**
     * Get price history for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Currency|null  $currency
     * @param  \DateTimeInterface|null  $from
     * @param  \DateTimeInterface|null  $to
     * @return \Illuminate\Support\Collection
     */
    public function getPriceHistory(
        ProductVariant $variant,
        ?Currency $currency = null,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): \Illuminate\Support\Collection {
        $query = PriceHistory::where('product_variant_id', $variant->id);

        if ($currency) {
            $query->where('currency_id', $currency->id);
        }

        if ($from) {
            $query->where('effective_from', '>=', $from);
        }

        if ($to) {
            $query->where(function ($q) use ($to) {
                $q->whereNull('effective_to')->orWhere('effective_to', '<=', $to);
            });
        }

        return $query->orderByDesc('effective_from')->get();
    }

    /**
     * Calculate margin.
     *
     * @param  ProductVariant  $variant
     * @param  int  $price
     * @return array
     */
    protected function calculateMargin(ProductVariant $variant, int $price): array
    {
        $costPrice = $variant->cost_price ?? 0;

        if ($costPrice <= 0 || $price <= 0) {
            return [
                'amount' => 0,
                'percentage' => 0.0,
            ];
        }

        $marginAmount = $price - $costPrice;
        $marginPercentage = ($marginAmount / $price) * 100;

        return [
            'amount' => $marginAmount,
            'percentage' => round($marginPercentage, 2),
        ];
    }

    /**
     * Get active flash deals.
     *
     * @param  Currency|null  $currency
     * @param  Channel|null  $channel
     * @return \Illuminate\Support\Collection
     */
    public function getActiveFlashDeals(?Currency $currency = null, ?Channel $channel = null): \Illuminate\Support\Collection
    {
        $query = VariantPrice::where('is_flash_deal', true)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());

        if ($currency) {
            $query->where('currency_id', $currency->id);
        }

        if ($channel) {
            $query->where(function ($q) use ($channel) {
                $q->whereNull('channel_id')->orWhere('channel_id', $channel->id);
            });
        }

        return $query->with('variant')->get();
    }
}
