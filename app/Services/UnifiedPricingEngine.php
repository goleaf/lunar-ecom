<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Services\PriorityPricingResolver;
use App\Services\PricingRuleEngine;
use App\Services\AdvancedPricingService;
use Lunar\Models\Currency;
use Lunar\Models\Channel;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Customer;
use Lunar\Facades\Taxes;
use Illuminate\Support\Collection;

/**
 * Unified Pricing Engine.
 * 
 * Always returns a standardized output format:
 * - Final price
 * - Original price
 * - Discount breakdown
 * - Applied rules
 * - Tax base
 * - Currency metadata
 */
class UnifiedPricingEngine
{
    protected PriorityPricingResolver $resolver;
    protected PricingRuleEngine $ruleEngine;
    protected AdvancedPricingService $advancedPricing;

    public function __construct(
        PriorityPricingResolver $resolver,
        PricingRuleEngine $ruleEngine,
        AdvancedPricingService $advancedPricing
    ) {
        $this->resolver = $resolver;
        $this->ruleEngine = $ruleEngine;
        $this->advancedPricing = $advancedPricing;
    }

    /**
     * Calculate price for variant with standardized output.
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
    public function calculatePrice(
        ProductVariant $variant,
        int $quantity = 1,
        ?Currency $currency = null,
        ?Channel $channel = null,
        ?CustomerGroup $customerGroup = null,
        ?Customer $customer = null,
        array $context = []
    ): array {
        // Get currency
        if (!$currency) {
            $currency = Currency::where('default', true)->first();
        }

        if (!$currency) {
            throw new \RuntimeException('No currency available for price calculation.');
        }

        // Check if price is locked
        if ($variant->price_locked) {
            return $this->getLockedPriceResponse($variant, $currency, $quantity, $channel, $customerGroup, $customer);
        }

        // Resolve base price using priority resolver
        $priceData = $this->resolver->resolvePrice(
            $variant,
            $quantity,
            $currency,
            $channel,
            $customerGroup,
            $customer,
            $context
        );

        if (!$priceData) {
            throw new \RuntimeException('No price found for variant.');
        }

        $originalPrice = $priceData['original_price'] ?? $priceData['price'];
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

        $finalPrice = $ruleResult['final_price'];
        $appliedRules = $ruleResult['applied_rules'] ?? collect();

        // Build discount breakdown
        $discountBreakdown = $this->buildDiscountBreakdown($appliedRules, $originalPrice, $finalPrice);

        // Calculate tax base
        $taxBase = $this->calculateTaxBase($variant, $finalPrice, $priceData['tax_inclusive'] ?? false);

        // Build currency metadata
        $currencyMetadata = $this->buildCurrencyMetadata($currency);

        return [
            'final_price' => $finalPrice,
            'original_price' => $originalPrice,
            'discount_breakdown' => $discountBreakdown,
            'applied_rules' => $this->formatAppliedRules($appliedRules),
            'tax_base' => $taxBase,
            'currency_metadata' => $currencyMetadata,
            'pricing_layer' => $priceData['layer'] ?? null,
            'pricing_source' => $priceData['source'] ?? null,
            'compare_at_price' => $priceData['compare_at_price'] ?? null,
            'quantity' => $quantity,
            'variant_id' => $variant->id,
        ];
    }

    /**
     * Get locked price response (when price is locked).
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @param  Customer|null  $customer
     * @return array
     */
    protected function getLockedPriceResponse(
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        ?Channel $channel,
        ?CustomerGroup $customerGroup,
        ?Customer $customer
    ): array {
        // Get base price without applying discounts
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

        $originalPrice = $priceData['original_price'] ?? $priceData['price'];
        $finalPrice = $originalPrice; // No discounts applied when locked

        $taxBase = $this->calculateTaxBase($variant, $finalPrice, $priceData['tax_inclusive'] ?? false);
        $currencyMetadata = $this->buildCurrencyMetadata($currency);

        return [
            'final_price' => $finalPrice,
            'original_price' => $originalPrice,
            'discount_breakdown' => [
                'total_discount' => 0,
                'total_discount_percentage' => 0.0,
                'discounts' => [],
            ],
            'applied_rules' => [],
            'tax_base' => $taxBase,
            'currency_metadata' => $currencyMetadata,
            'pricing_layer' => $priceData['layer'] ?? null,
            'pricing_source' => $priceData['source'] ?? null,
            'compare_at_price' => $priceData['compare_at_price'] ?? null,
            'quantity' => $quantity,
            'variant_id' => $variant->id,
            'price_locked' => true,
        ];
    }

    /**
     * Build discount breakdown.
     *
     * @param  Collection  $appliedRules
     * @param  int  $originalPrice
     * @param  int  $finalPrice
     * @return array
     */
    protected function buildDiscountBreakdown(Collection $appliedRules, int $originalPrice, int $finalPrice): array
    {
        $totalDiscount = $originalPrice - $finalPrice;
        $totalDiscountPercentage = $originalPrice > 0 
            ? round(($totalDiscount / $originalPrice) * 100, 2) 
            : 0.0;

        $discounts = $appliedRules->map(function ($rule) {
            return [
                'rule_id' => $rule['rule']->id ?? null,
                'rule_name' => $rule['rule']->name ?? null,
                'rule_type' => $rule['type'] ?? null,
                'price_before' => $rule['price_before'] ?? 0,
                'price_after' => $rule['price_after'] ?? 0,
                'adjustment' => $rule['adjustment'] ?? 0,
                'adjustment_percentage' => $rule['price_before'] > 0 
                    ? round(($rule['adjustment'] / $rule['price_before']) * 100, 2) 
                    : 0.0,
            ];
        })->toArray();

        return [
            'total_discount' => $totalDiscount,
            'total_discount_percentage' => $totalDiscountPercentage,
            'discounts' => $discounts,
        ];
    }

    /**
     * Format applied rules for output.
     *
     * @param  Collection  $appliedRules
     * @return array
     */
    protected function formatAppliedRules(Collection $appliedRules): array
    {
        return $appliedRules->map(function ($rule) {
            return [
                'id' => $rule['rule']->id ?? null,
                'name' => $rule['rule']->name ?? null,
                'handle' => $rule['rule']->handle ?? null,
                'type' => $rule['type'] ?? null,
                'priority' => $rule['rule']->priority ?? 0,
                'is_stackable' => $rule['rule']->is_stackable ?? false,
                'scope' => $rule['rule']->scope ?? null,
                'conditions' => $rule['rule']->conditions ?? [],
                'price_before' => $rule['price_before'] ?? 0,
                'price_after' => $rule['price_after'] ?? 0,
                'adjustment' => $rule['adjustment'] ?? 0,
            ];
        })->toArray();
    }

    /**
     * Calculate tax base.
     *
     * @param  ProductVariant  $variant
     * @param  int  $price
     * @param  bool  $taxInclusive
     * @return array
     */
    protected function calculateTaxBase(ProductVariant $variant, int $price, bool $taxInclusive = false): array
    {
        // Get tax rates for variant
        $taxRates = Taxes::for($variant)->get();

        if ($taxInclusive) {
            // Price includes tax, calculate base
            $totalTaxRate = $taxRates->sum('percentage');
            $taxMultiplier = 1 + ($totalTaxRate / 100);
            $basePrice = (int)round($price / $taxMultiplier);
            $taxAmount = $price - $basePrice;
        } else {
            // Price excludes tax, calculate tax
            $basePrice = $price;
            $totalTaxRate = $taxRates->sum('percentage');
            $taxAmount = (int)round($price * ($totalTaxRate / 100));
        }

        $taxBreakdown = $taxRates->map(function ($rate) use ($basePrice, $taxInclusive) {
            $rateAmount = $taxInclusive
                ? (int)round(($basePrice * ($rate->percentage / 100)) / (1 + ($rate->percentage / 100)))
                : (int)round($basePrice * ($rate->percentage / 100));

            return [
                'name' => $rate->name ?? 'Tax',
                'percentage' => $rate->percentage ?? 0.0,
                'amount' => $rateAmount,
            ];
        })->toArray();

        return [
            'base_price' => $basePrice,
            'tax_amount' => $taxAmount,
            'total_price' => $taxInclusive ? $price : ($basePrice + $taxAmount),
            'tax_inclusive' => $taxInclusive,
            'tax_breakdown' => $taxBreakdown,
            'total_tax_rate' => $taxRates->sum('percentage'),
        ];
    }

    /**
     * Build currency metadata.
     *
     * @param  Currency  $currency
     * @return array
     */
    protected function buildCurrencyMetadata(Currency $currency): array
    {
        return [
            'id' => $currency->id,
            'code' => $currency->code,
            'name' => $currency->name,
            'symbol' => $currency->symbol ?? $currency->code,
            'exchange_rate' => $currency->exchange_rate ?? 1.0,
            'decimal_places' => $currency->decimal_places ?? 2,
            'is_default' => $currency->default ?? false,
            'formatted_symbol' => $currency->formatted_symbol ?? $currency->code,
        ];
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
        // Use the same calculation but mark as simulation
        $result = $this->calculatePrice(
            $variant,
            $quantity,
            $currency,
            $channel,
            $customerGroup,
            $customer,
            $context
        );

        $result['is_simulation'] = true;
        $result['simulated_at'] = now()->toIso8601String();

        return $result;
    }
}


