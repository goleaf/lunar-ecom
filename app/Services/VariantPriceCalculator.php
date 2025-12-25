<?php

namespace App\Services;

use App\Models\ProductVariant;
use Lunar\Models\Price;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Illuminate\Support\Collection;

/**
 * Service for calculating variant prices with tiered pricing support.
 * 
 * Handles:
 * - Base pricing
 * - Tiered/quantity-based pricing
 * - Customer group pricing
 * - Price overrides
 * - Compare-at prices
 */
class VariantPriceCalculator
{
    /**
     * Calculate the price for a variant based on quantity and customer group.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  Currency|null  $currency
     * @param  CustomerGroup|Collection|array|null  $customerGroups
     * @return array Price information array
     */
    public function calculatePrice(
        ProductVariant $variant,
        int $quantity = 1,
        ?Currency $currency = null,
        $customerGroups = null
    ): array {
        // Use price override if set
        if ($variant->price_override !== null) {
            return $this->formatPriceResponse(
                $variant->price_override,
                $variant->compare_at_price,
                $currency
            );
        }

        // Get currency
        if (!$currency) {
            $currency = Currency::where('default', true)->first();
        }

        if (!$currency) {
            throw new \RuntimeException('No currency available for price calculation.');
        }

        // Get the best matching price
        $price = $this->getBestPrice($variant, $quantity, $currency, $customerGroups);

        if (!$price) {
            return [
                'price' => null,
                'compare_price' => null,
                'formatted_price' => null,
                'formatted_compare_price' => null,
                'savings' => null,
                'savings_percentage' => null,
            ];
        }

        // Use variant compare_at_price if set, otherwise use price's compare_price
        $comparePrice = $variant->compare_at_price ?? $price->compare_price;

        return $this->formatPriceResponse(
            $price->price,
            $comparePrice,
            $currency,
            $price->min_quantity
        );
    }

    /**
     * Get the best matching price for a variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  Currency  $currency
     * @param  CustomerGroup|Collection|array|null  $customerGroups
     * @return Price|null
     */
    protected function getBestPrice(
        ProductVariant $variant,
        int $quantity,
        Currency $currency,
        $customerGroups = null
    ): ?Price {
        $query = Price::where('priceable_type', ProductVariant::class)
            ->where('priceable_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('min_quantity', 'desc');

        // Apply customer group filter if provided
        if ($customerGroups) {
            $customerGroupIds = $this->normalizeCustomerGroups($customerGroups);
            
            $query->where(function ($q) use ($customerGroupIds) {
                $q->whereNull('customer_group_id')
                  ->orWhereIn('customer_group_id', $customerGroupIds);
            });
        } else {
            $query->whereNull('customer_group_id');
        }

        return $query->first();
    }

    /**
     * Normalize customer groups to an array of IDs.
     *
     * @param  CustomerGroup|Collection|array|null  $customerGroups
     * @return array
     */
    protected function normalizeCustomerGroups($customerGroups): array
    {
        if ($customerGroups instanceof CustomerGroup) {
            return [$customerGroups->id];
        }

        if ($customerGroups instanceof Collection) {
            return $customerGroups->pluck('id')->toArray();
        }

        if (is_array($customerGroups)) {
            return array_map(function ($group) {
                return $group instanceof CustomerGroup ? $group->id : $group;
            }, $customerGroups);
        }

        return [];
    }

    /**
     * Format price response array.
     *
     * @param  int  $price
     * @param  int|null  $comparePrice
     * @param  Currency  $currency
     * @param  int|null  $minQuantity
     * @return array
     */
    protected function formatPriceResponse(
        int $price,
        ?int $comparePrice,
        Currency $currency,
        ?int $minQuantity = null
    ): array {
        $priceDecimal = $price / 100;
        $comparePriceDecimal = $comparePrice ? $comparePrice / 100 : null;

        $savings = null;
        $savingsPercentage = null;

        if ($comparePrice && $comparePrice > $price) {
            $savings = ($comparePrice - $price) / 100;
            $savingsPercentage = round(($savings / ($comparePrice / 100)) * 100, 2);
        }

        return [
            'price' => $price,
            'price_decimal' => $priceDecimal,
            'compare_price' => $comparePrice,
            'compare_price_decimal' => $comparePriceDecimal,
            'formatted_price' => $this->formatCurrency($priceDecimal, $currency),
            'formatted_compare_price' => $comparePriceDecimal ? $this->formatCurrency($comparePriceDecimal, $currency) : null,
            'savings' => $savings,
            'savings_percentage' => $savingsPercentage,
            'min_quantity' => $minQuantity,
            'currency' => $currency->code,
        ];
    }

    /**
     * Format a decimal price as currency.
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
     * Get all available price tiers for a variant.
     *
     * @param  ProductVariant  $variant
     * @param  Currency|null  $currency
     * @param  CustomerGroup|Collection|array|null  $customerGroups
     * @return Collection
     */
    public function getPriceTiers(
        ProductVariant $variant,
        ?Currency $currency = null,
        $customerGroups = null
    ): Collection {
        if (!$currency) {
            $currency = Currency::where('default', true)->first();
        }

        if (!$currency) {
            return collect();
        }

        $query = Price::where('priceable_type', ProductVariant::class)
            ->where('priceable_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->orderBy('min_quantity', 'asc');

        // Apply customer group filter if provided
        if ($customerGroups) {
            $customerGroupIds = $this->normalizeCustomerGroups($customerGroups);
            
            $query->where(function ($q) use ($customerGroupIds) {
                $q->whereNull('customer_group_id')
                  ->orWhereIn('customer_group_id', $customerGroupIds);
            });
        } else {
            $query->whereNull('customer_group_id');
        }

        return $query->get()->map(function ($price) use ($currency) {
            return [
                'min_quantity' => $price->min_quantity,
                'price' => $price->price,
                'price_decimal' => $price->price / 100,
                'formatted_price' => $this->formatCurrency($price->price / 100, $currency),
                'compare_price' => $price->compare_price,
                'compare_price_decimal' => $price->compare_price ? $price->compare_price / 100 : null,
                'formatted_compare_price' => $price->compare_price ? $this->formatCurrency($price->compare_price / 100, $currency) : null,
            ];
        });
    }

    /**
     * Calculate profit margin for a variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  Currency|null  $currency
     * @return array|null
     */
    public function calculateProfitMargin(
        ProductVariant $variant,
        int $quantity = 1,
        ?Currency $currency = null
    ): ?array {
        if (!$variant->cost_price) {
            return null;
        }

        $priceInfo = $this->calculatePrice($variant, $quantity, $currency);
        
        if (!$priceInfo['price']) {
            return null;
        }

        $costPrice = $variant->cost_price / 100;
        $sellingPrice = $priceInfo['price_decimal'];
        $profit = $sellingPrice - $costPrice;
        $margin = $costPrice > 0 ? ($profit / $costPrice) * 100 : 0;
        $marginPercentage = ($profit / $sellingPrice) * 100;

        return [
            'cost_price' => $variant->cost_price,
            'cost_price_decimal' => $costPrice,
            'selling_price' => $priceInfo['price'],
            'selling_price_decimal' => $sellingPrice,
            'profit' => $profit * 100, // In cents
            'profit_decimal' => $profit,
            'margin' => round($margin, 2),
            'margin_percentage' => round($marginPercentage, 2),
        ];
    }
}

