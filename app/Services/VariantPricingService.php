<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantPrice;
use App\Models\VariantPriceHook;
use Lunar\Models\Currency;
use Lunar\Models\Channel;
use Lunar\Models\CustomerGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Comprehensive variant pricing service.
 * 
 * Handles:
 * - Base price per currency
 * - Compare-at price
 * - Cost price
 * - Channel-specific pricing
 * - Customer-group pricing
 * - Tiered pricing
 * - Time-limited pricing
 * - Tax-inclusive/exclusive
 * - Price rounding
 * - MAP pricing
 * - Price locks
 * - Discount overrides
 * - Dynamic pricing hooks
 */
class VariantPricingService
{
    /**
     * Calculate price for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  Currency|null  $currency
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @param  bool  $includeTax
     * @return array
     */
    public function calculatePrice(
        ProductVariant $variant,
        int $quantity = 1,
        ?Currency $currency = null,
        ?Channel $channel = null,
        ?CustomerGroup $customerGroup = null,
        bool $includeTax = false
    ): array {
        // Get currency
        if (!$currency) {
            $currency = Currency::where('default', true)->first();
        }

        if (!$currency) {
            throw new \RuntimeException('No currency available for price calculation.');
        }

        // Check price lock
        if ($variant->price_locked) {
            return $this->getLockedPrice($variant, $currency, $quantity, $includeTax);
        }

        // Try dynamic pricing hooks first
        $hookPrice = $this->getHookPrice($variant, $currency, $quantity, $channel, $customerGroup);
        if ($hookPrice !== null) {
            return $this->formatPriceResponse($hookPrice, $variant, $currency, $quantity, $includeTax);
        }

        // Get best matching price
        $price = $this->findBestPrice($variant, $currency, $quantity, $channel, $customerGroup);

        // Apply discount override if applicable
        $price = $this->applyDiscountOverride($variant, $price, $quantity, $channel, $customerGroup);

        // Apply MAP pricing enforcement
        $price = $this->enforceMAP($variant, $price, $currency);

        // Apply price rounding
        $price = $this->applyRounding($variant, $price, $currency);

        // Get compare-at price
        $compareAtPrice = $this->getCompareAtPrice($variant, $currency, $quantity, $channel, $customerGroup);

        return $this->formatPriceResponse($price, $variant, $currency, $quantity, $includeTax, $compareAtPrice);
    }

    /**
     * Find best matching price.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @return int
     */
    protected function findBestPrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        ?Channel $channel,
        ?CustomerGroup $customerGroup
    ): int {
        // Query for matching prices
        $query = VariantPrice::where('variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->active()
            ->forQuantity($quantity)
            ->orderByDesc('priority');

        // Apply filters
        if ($channel) {
            $query->forChannel($channel->id);
        }

        if ($customerGroup) {
            $query->forCustomerGroup($customerGroup->id);
        }

        // Get best match
        $bestPrice = $query->first();

        if ($bestPrice) {
            return $bestPrice->price;
        }

        // Fallback to variant's price_override or Lunar pricing
        if ($variant->price_override !== null) {
            return $variant->price_override;
        }

        // Use Lunar's pricing system
        $pricing = \Lunar\Facades\Pricing::qty($quantity)->for($variant);
        
        if ($customerGroup) {
            $pricing = $pricing->customerGroup($customerGroup);
        }

        $response = $pricing->get();
        return $response->matched?->price?->value ?? 0;
    }

    /**
     * Get compare-at price.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @return int|null
     */
    protected function getCompareAtPrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        ?Channel $channel,
        ?CustomerGroup $customerGroup
    ): ?int {
        // Check variant-specific compare-at price
        if ($variant->compare_at_price !== null) {
            return $variant->compare_at_price;
        }

        // Check VariantPrice compare-at price
        $query = VariantPrice::where('variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->active()
            ->forQuantity($quantity);

        if ($channel) {
            $query->forChannel($channel->id);
        }

        if ($customerGroup) {
            $query->forCustomerGroup($customerGroup->id);
        }

        $price = $query->orderByDesc('priority')->first();
        return $price?->compare_at_price;
    }

    /**
     * Get hook price.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @return int|null
     */
    protected function getHookPrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        ?Channel $channel,
        ?CustomerGroup $customerGroup
    ): ?int {
        $hooks = VariantPriceHook::where('variant_id', $variant->id)
            ->active()
            ->orderByDesc('priority')
            ->get();

        foreach ($hooks as $hook) {
            // Check cache first
            $cachedPrice = $hook->getCachedPrice();
            if ($cachedPrice !== null) {
                return $cachedPrice;
            }

            // Execute hook
            $price = $this->executeHook($hook, $variant, $currency, $quantity, $channel, $customerGroup);
            
            if ($price !== null) {
                // Cache the result
                $hook->updateCache($price);
                return $price;
            }
        }

        return null;
    }

    /**
     * Execute pricing hook.
     *
     * @param  VariantPriceHook  $hook
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @return int|null
     */
    protected function executeHook(
        VariantPriceHook $hook,
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        ?Channel $channel,
        ?CustomerGroup $customerGroup
    ): ?int {
        $identifier = $hook->hook_identifier;
        $config = $hook->config ?? [];

        // Dispatch event for hook execution
        $event = new \App\Events\VariantPriceHookExecuting($hook, $variant, $currency, $quantity, $channel, $customerGroup);
        event($event);

        // Check if event was handled
        if ($event->price !== null) {
            return $event->price;
        }

        // Try to resolve hook handler
        $handler = $this->resolveHookHandler($hook->hook_type, $identifier);
        
        if ($handler && method_exists($handler, 'calculatePrice')) {
            return $handler->calculatePrice($variant, $currency, $quantity, $channel, $customerGroup, $config);
        }

        return null;
    }

    /**
     * Resolve hook handler.
     *
     * @param  string  $type
     * @param  string  $identifier
     * @return object|null
     */
    protected function resolveHookHandler(string $type, string $identifier): ?object
    {
        $handlerClass = config("lunar.pricing.hooks.{$type}.{$identifier}");

        if ($handlerClass && class_exists($handlerClass)) {
            return app($handlerClass);
        }

        return null;
    }

    /**
     * Apply discount override.
     *
     * @param  ProductVariant  $variant
     * @param  int  $price
     * @param  int  $quantity
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @return int
     */
    protected function applyDiscountOverride(
        ProductVariant $variant,
        int $price,
        int $quantity,
        ?Channel $channel,
        ?CustomerGroup $customerGroup
    ): int {
        $override = $variant->discount_override;

        if (!$override) {
            return $price;
        }

        // Check if override applies to current context
        if (isset($override['channels']) && $channel && !in_array($channel->id, $override['channels'])) {
            return $price;
        }

        if (isset($override['customer_groups']) && $customerGroup && !in_array($customerGroup->id, $override['customer_groups'])) {
            return $price;
        }

        // Apply discount
        if (isset($override['discount_type'])) {
            return match($override['discount_type']) {
                'fixed' => $price - ($override['discount_amount'] ?? 0),
                'percentage' => $price - (int)($price * ($override['discount_amount'] ?? 0) / 100),
                'override' => $override['discount_amount'] ?? $price,
                default => $price,
            };
        }

        return $price;
    }

    /**
     * Enforce MAP pricing.
     *
     * @param  ProductVariant  $variant
     * @param  int  $price
     * @param  Currency  $currency
     * @return int
     */
    protected function enforceMAP(ProductVariant $variant, int $price, Currency $currency): int
    {
        if (!$variant->map_price) {
            return $price;
        }

        // MAP is typically per currency, but we'll use variant's MAP if set
        // In production, you might want MAP per currency
        return max($price, $variant->map_price);
    }

    /**
     * Apply price rounding.
     *
     * @param  ProductVariant  $variant
     * @param  int  $price
     * @param  Currency  $currency
     * @return int
     */
    protected function applyRounding(ProductVariant $variant, int $price, Currency $currency): int
    {
        $rules = $variant->price_rounding_rules;

        if (!$rules) {
            return $price;
        }

        $method = $rules['method'] ?? 'none';
        $precision = $rules['precision'] ?? 0;

        return match($method) {
            'round' => (int)round($price / 100, $precision) * 100,
            'round_up' => (int)ceil($price / 100 / pow(10, $precision)) * pow(10, $precision) * 100,
            'round_down' => (int)floor($price / 100 / pow(10, $precision)) * pow(10, $precision) * 100,
            'nearest' => $this->roundToNearest($price, $rules['nearest'] ?? 100),
            default => $price,
        };
    }

    /**
     * Round to nearest value.
     *
     * @param  int  $price
     * @param  int  $nearest
     * @return int
     */
    protected function roundToNearest(int $price, int $nearest): int
    {
        return (int)(round($price / $nearest) * $nearest);
    }

    /**
     * Get locked price.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  bool  $includeTax
     * @return array
     */
    protected function getLockedPrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        bool $includeTax
    ): array {
        $price = $variant->price_override ?? 0;
        $compareAtPrice = $variant->compare_at_price;

        return $this->formatPriceResponse($price, $variant, $currency, $quantity, $includeTax, $compareAtPrice);
    }

    /**
     * Format price response.
     *
     * @param  int  $price
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  bool  $includeTax
     * @param  int|null  $compareAtPrice
     * @return array
     */
    protected function formatPriceResponse(
        int $price,
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        bool $includeTax,
        ?int $compareAtPrice = null
    ): array {
        $priceDecimal = $price / 100;
        $compareAtPriceDecimal = $compareAtPrice ? $compareAtPrice / 100 : null;

        // Calculate tax if needed
        $taxAmount = 0;
        if ($includeTax && !$variant->tax_inclusive) {
            // Calculate tax based on variant's tax class
            $taxRate = $variant->taxClass?->taxRate ?? 0;
            $taxAmount = (int)($price * $taxRate / 100);
        }

        $finalPrice = $variant->tax_inclusive ? $price : ($price + $taxAmount);

        return [
            'price' => $finalPrice,
            'price_decimal' => $finalPrice / 100,
            'base_price' => $price,
            'base_price_decimal' => $priceDecimal,
            'compare_at_price' => $compareAtPrice,
            'compare_at_price_decimal' => $compareAtPriceDecimal,
            'cost_price' => $variant->cost_price,
            'cost_price_decimal' => $variant->cost_price ? $variant->cost_price / 100 : null,
            'margin' => $variant->cost_price ? $price - $variant->cost_price : null,
            'margin_percentage' => $variant->cost_price && $price > 0 
                ? round((($price - $variant->cost_price) / $price) * 100, 2) 
                : null,
            'tax_inclusive' => $variant->tax_inclusive,
            'tax_amount' => $taxAmount,
            'formatted_price' => $this->formatCurrency($finalPrice / 100, $currency),
            'formatted_compare_price' => $compareAtPriceDecimal ? $this->formatCurrency($compareAtPriceDecimal, $currency) : null,
            'quantity' => $quantity,
            'currency' => $currency->code,
            'price_locked' => $variant->price_locked,
            'map_price' => $variant->map_price,
            'map_price_decimal' => $variant->map_price ? $variant->map_price / 100 : null,
        ];
    }

    /**
     * Format currency.
     *
     * @param  float  $amount
     * @param  Currency  $currency
     * @return string
     */
    protected function formatCurrency(float $amount, Currency $currency): string
    {
        $formatter = new \NumberFormatter(
            app()->getLocale(),
            \NumberFormatter::CURRENCY
        );

        return $formatter->formatCurrency($amount, $currency->code);
    }

    /**
     * Set price for variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return VariantPrice
     */
    public function setPrice(ProductVariant $variant, array $data): VariantPrice
    {
        return VariantPrice::create(array_merge([
            'variant_id' => $variant->id,
        ], $data));
    }

    /**
     * Get tiered pricing for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Currency|null  $currency
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @return Collection
     */
    public function getTieredPricing(
        ProductVariant $variant,
        ?Currency $currency = null,
        ?Channel $channel = null,
        ?CustomerGroup $customerGroup = null
    ): Collection {
        if (!$currency) {
            $currency = Currency::where('default', true)->first();
        }

        $query = VariantPrice::where('variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->active()
            ->orderBy('min_quantity');

        if ($channel) {
            $query->forChannel($channel->id);
        }

        if ($customerGroup) {
            $query->forCustomerGroup($customerGroup->id);
        }

        return $query->get()->map(function ($price) use ($currency) {
            return [
                'min_quantity' => $price->min_quantity,
                'max_quantity' => $price->max_quantity,
                'price' => $price->price,
                'price_decimal' => $price->price / 100,
                'formatted_price' => $this->formatCurrency($price->price / 100, $currency),
                'compare_at_price' => $price->compare_at_price,
                'compare_at_price_decimal' => $price->compare_at_price ? $price->compare_at_price / 100 : null,
                'starts_at' => $price->starts_at?->toDateTimeString(),
                'ends_at' => $price->ends_at?->toDateTimeString(),
            ];
        });
    }
}

