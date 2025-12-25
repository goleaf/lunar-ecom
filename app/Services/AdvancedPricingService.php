<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\PriceMatrix;
use Lunar\Models\Price;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Channel;
use Lunar\Models\TaxClass;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Advanced Pricing Service - Comprehensive pricing engine.
 * 
 * Handles all pricing scenarios:
 * - Multi-currency pricing
 * - Per-variant pricing
 * - Customer-group pricing
 * - Tiered pricing (bulk discounts)
 * - Time-based pricing (sales)
 * - Channel-specific pricing
 * - Tax-inclusive / tax-exclusive prices
 * - Automatic currency conversion
 * - Rounding rules per currency
 */
class AdvancedPricingService
{
    protected MatrixPricingService $matrixPricingService;

    public function __construct(MatrixPricingService $matrixPricingService)
    {
        $this->matrixPricingService = $matrixPricingService;
    }

    /**
     * Calculate price for a variant with full context.
     *
     * @param ProductVariant $variant
     * @param int $quantity
     * @param Currency|null $currency
     * @param CustomerGroup|null $customerGroup
     * @param Channel|null $channel
     * @param bool $includeTax
     * @return array
     */
    public function calculatePrice(
        ProductVariant $variant,
        int $quantity = 1,
        ?Currency $currency = null,
        ?CustomerGroup $customerGroup = null,
        ?Channel $channel = null,
        bool $includeTax = false
    ): array {
        // Get currency
        if (!$currency) {
            $currency = Currency::where('default', true)->first();
        }

        if (!$currency) {
            throw new \RuntimeException('No currency available for price calculation.');
        }

        // Get base price from Lunar pricing system or variant override
        $basePrice = $this->getBasePrice($variant, $quantity, $currency, $customerGroup, $channel);

        // Apply matrix pricing (tiered, customer group, regional)
        $matrixPrice = $this->matrixPricingService->calculatePrice(
            $variant,
            $quantity,
            $currency,
            $customerGroup
        );

        // Use matrix price if available and better, otherwise use base price
        $finalPrice = $matrixPrice['price'] ?? $basePrice;

        // Apply currency conversion if needed
        if ($currency->auto_convert && !$currency->default) {
            $finalPrice = $this->convertPrice($finalPrice, Currency::where('default', true)->first(), $currency);
        }

        // Apply rounding rules
        $finalPrice = $this->roundPrice($finalPrice, $currency);

        // Calculate tax
        $taxAmount = 0;
        $priceExTax = $finalPrice;
        $priceIncTax = $finalPrice;

        if ($includeTax) {
            $taxAmount = $this->calculateTax($variant, $finalPrice, $currency);
            $priceIncTax = $finalPrice;
            $priceExTax = $finalPrice - $taxAmount;
        } else {
            $taxAmount = $this->calculateTax($variant, $finalPrice, $currency);
            $priceExTax = $finalPrice;
            $priceIncTax = $finalPrice + $taxAmount;
        }

        // Get compare-at price
        $comparePrice = $variant->compare_at_price ?? null;
        if ($comparePrice && $currency->auto_convert && !$currency->default) {
            $comparePrice = $this->convertPrice($comparePrice, Currency::where('default', true)->first(), $currency);
            $comparePrice = $this->roundPrice($comparePrice, $currency);
        }

        return [
            'price' => $finalPrice,
            'price_decimal' => $finalPrice / 100,
            'price_ex_tax' => $priceExTax,
            'price_ex_tax_decimal' => $priceExTax / 100,
            'price_inc_tax' => $priceIncTax,
            'price_inc_tax_decimal' => $priceIncTax / 100,
            'tax_amount' => $taxAmount,
            'tax_amount_decimal' => $taxAmount / 100,
            'compare_price' => $comparePrice,
            'compare_price_decimal' => $comparePrice ? $comparePrice / 100 : null,
            'formatted_price' => $this->formatPrice($finalPrice, $currency),
            'formatted_price_ex_tax' => $this->formatPrice($priceExTax, $currency),
            'formatted_price_inc_tax' => $this->formatPrice($priceIncTax, $currency),
            'formatted_compare_price' => $comparePrice ? $this->formatPrice($comparePrice, $currency) : null,
            'currency' => $currency->code,
            'currency_id' => $currency->id,
            'quantity' => $quantity,
            'channel_id' => $channel?->id,
            'customer_group_id' => $customerGroup?->id,
            'savings' => $comparePrice && $comparePrice > $finalPrice 
                ? ($comparePrice - $finalPrice) / 100 
                : null,
            'savings_percentage' => $comparePrice && $comparePrice > $finalPrice
                ? round((($comparePrice - $finalPrice) / $comparePrice) * 100, 2)
                : null,
        ];
    }

    /**
     * Get base price from Lunar pricing system or variant override.
     */
    protected function getBasePrice(
        ProductVariant $variant,
        int $quantity,
        Currency $currency,
        ?CustomerGroup $customerGroup,
        ?Channel $channel
    ): int {
        // Check for variant price override
        if ($variant->price_override !== null) {
            return $variant->price_override;
        }

        // Check for channel-specific price
        if ($channel) {
            $channelPrice = Price::where('priceable_type', ProductVariant::class)
                ->where('priceable_id', $variant->id)
                ->where('currency_id', $currency->id)
                ->where('channel_id', $channel->id)
                ->when($customerGroup, function ($q) use ($customerGroup) {
                    $q->where('customer_group_id', $customerGroup->id);
                })
                ->whereNull('customer_group_id') // Fallback to non-group price
                ->orderBy('tier')
                ->first();

            if ($channelPrice) {
                return $channelPrice->price;
            }
        }

        // Use Lunar's pricing facade
        $pricing = \Lunar\Facades\Pricing::qty($quantity)->for($variant);
        
        if ($currency) {
            $pricing = $pricing->currency($currency);
        }
        
        if ($customerGroup) {
            $pricing = $pricing->customerGroup($customerGroup);
        }

        $response = $pricing->get();
        return $response->matched?->price?->value ?? 0;
    }

    /**
     * Convert price from one currency to another.
     */
    protected function convertPrice(int $price, Currency $fromCurrency, Currency $toCurrency): int
    {
        if ($fromCurrency->id === $toCurrency->id) {
            return $price;
        }

        $priceDecimal = $price / 100;
        $converted = \App\Lunar\Currencies\CurrencyHelper::convert(
            $priceDecimal,
            $fromCurrency,
            $toCurrency
        );

        return (int) round($converted * 100);
    }

    /**
     * Round price according to currency rounding rules.
     */
    protected function roundPrice(int $price, Currency $currency): int
    {
        $priceDecimal = $price / 100;
        $precision = (float) $currency->rounding_precision;

        if ($precision <= 0) {
            return $price; // No rounding
        }

        $rounded = match ($currency->rounding_mode) {
            'none' => $priceDecimal,
            'up' => ceil($priceDecimal / $precision) * $precision,
            'down' => floor($priceDecimal / $precision) * $precision,
            'nearest' => round($priceDecimal / $precision) * $precision,
            'nearest_up' => $priceDecimal % $precision == 0 
                ? $priceDecimal 
                : ceil($priceDecimal / $precision) * $precision,
            'nearest_down' => $priceDecimal % $precision == 0 
                ? $priceDecimal 
                : floor($priceDecimal / $precision) * $precision,
            default => round($priceDecimal / $precision) * $precision,
        };

        return (int) round($rounded * 100);
    }

    /**
     * Calculate tax for a price.
     */
    protected function calculateTax(ProductVariant $variant, int $price, Currency $currency): int
    {
        $taxClass = $variant->taxClass;
        
        if (!$taxClass) {
            return 0;
        }

        // Get tax rate (simplified - you may need to get actual tax rate based on location)
        // This is a placeholder - implement based on your tax calculation logic
        $taxRate = 0.20; // 20% default - replace with actual tax rate lookup

        $priceDecimal = $price / 100;
        $taxAmount = $priceDecimal * $taxRate;

        return (int) round($taxAmount * 100);
    }

    /**
     * Format price with currency.
     */
    protected function formatPrice(int $price, Currency $currency): string
    {
        $priceDecimal = $price / 100;
        
        $formatter = new \NumberFormatter(
            app()->getLocale(),
            \NumberFormatter::CURRENCY
        );

        return $formatter->formatCurrency($priceDecimal, $currency->code);
    }

    /**
     * Get tiered pricing for a variant.
     */
    public function getTieredPricing(
        ProductVariant $variant,
        ?Currency $currency = null,
        ?CustomerGroup $customerGroup = null,
        ?Channel $channel = null
    ): Collection {
        return $this->matrixPricingService->getTieredPricing(
            $variant,
            $currency,
            $customerGroup
        );
    }

    /**
     * Get active sales/promotions for a variant.
     */
    public function getActiveSales(
        ProductVariant $variant,
        ?Currency $currency = null,
        ?CustomerGroup $customerGroup = null
    ): Collection {
        $product = $variant->product;
        $now = Carbon::now();

        return PriceMatrix::forProduct($product->id)
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get price for multiple variants (for bundle/cart calculations).
     */
    public function calculateBulkPrice(
        array $variants, // ['variant_id' => quantity]
        ?Currency $currency = null,
        ?CustomerGroup $customerGroup = null,
        ?Channel $channel = null,
        bool $includeTax = false
    ): array {
        $totalPrice = 0;
        $totalTax = 0;
        $variantPrices = [];

        foreach ($variants as $variantId => $quantity) {
            $variant = ProductVariant::find($variantId);
            if (!$variant) {
                continue;
            }

            $priceData = $this->calculatePrice(
                $variant,
                $quantity,
                $currency,
                $customerGroup,
                $channel,
                $includeTax
            );

            $variantPrices[$variantId] = $priceData;
            $totalPrice += $priceData['price'] * $quantity;
            $totalTax += ($priceData['tax_amount'] ?? 0) * $quantity;
        }

        $currency = $currency ?? Currency::where('default', true)->first();

        return [
            'total_price' => $totalPrice,
            'total_price_decimal' => $totalPrice / 100,
            'total_tax' => $totalTax,
            'total_tax_decimal' => $totalTax / 100,
            'total_price_ex_tax' => $totalPrice - $totalTax,
            'total_price_ex_tax_decimal' => ($totalPrice - $totalTax) / 100,
            'total_price_inc_tax' => $totalPrice + $totalTax,
            'total_price_inc_tax_decimal' => ($totalPrice + $totalTax) / 100,
            'formatted_total_price' => $this->formatPrice($totalPrice, $currency),
            'formatted_total_tax' => $this->formatPrice($totalTax, $currency),
            'variant_prices' => $variantPrices,
            'currency' => $currency->code,
        ];
    }
}

