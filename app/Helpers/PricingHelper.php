<?php

namespace App\Helpers;

use App\Models\ProductVariant;
use App\Services\MatrixPricingService;
use Lunar\Facades\Pricing;
use Lunar\Models\Currency;

class PricingHelper
{
    /**
     * Get formatted price with savings.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return array
     */
    public static function getPricingWithSavings(ProductVariant $variant, array $context = []): array
    {
        $service = app(MatrixPricingService::class);
        $pricing = $service->calculatePrice($variant, $context);

        return [
            'price' => $pricing['price'],
            'base_price' => $pricing['base_price'],
            'savings' => $pricing['savings'],
            'savings_percentage' => $pricing['savings_percentage'],
            'formatted_price' => self::formatPrice($pricing['price']),
            'formatted_base_price' => self::formatPrice($pricing['base_price']),
            'formatted_savings' => self::formatPrice($pricing['savings']),
        ];
    }

    /**
     * Format price for display.
     *
     * @param  float  $price
     * @return string
     */
    public static function formatPrice(float $price): string
    {
        $currency = Currency::getDefault();
        return Pricing::format($price * 100, $currency);
    }

    /**
     * Get tiered pricing table data.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return array
     */
    public static function getTieredPricingTable(ProductVariant $variant, array $context = []): array
    {
        $service = app(MatrixPricingService::class);
        $tiers = $service->getTieredPricing($variant, $context);

        return array_map(function ($tier) {
            return [
                'tier_name' => $tier['tier_name'] ?? "{$tier['min_quantity']}+ units",
                'min_quantity' => $tier['min_quantity'],
                'max_quantity' => $tier['max_quantity'],
                'price' => $tier['price'],
                'formatted_price' => self::formatPrice($tier['price']),
                'base_price' => $tier['base_price'],
                'formatted_base_price' => self::formatPrice($tier['base_price']),
                'savings' => $tier['savings'],
                'formatted_savings' => self::formatPrice($tier['savings']),
                'savings_percentage' => $tier['savings_percentage'],
            ];
        }, $tiers);
    }
}


