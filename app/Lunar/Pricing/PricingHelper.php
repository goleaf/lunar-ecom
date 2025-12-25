<?php

namespace App\Lunar\Pricing;

use Lunar\DataTypes\Price as PriceDataType;
use Lunar\Facades\Pricing;
use Lunar\Models\Currency;
use Lunar\Models\Price;
use Lunar\Models\ProductVariant;

/**
 * Helper class for working with Lunar Pricing.
 * 
 * Provides convenience methods for formatting prices and working with price data types.
 * See: https://docs.lunarphp.com/1.x/reference/pricing
 */
class PricingHelper
{
    /**
     * Format a price value.
     * 
     * @param int $value Price value in smallest currency unit (cents)
     * @param Currency|null $currency Currency instance (defaults to default currency)
     * @param int $unitQuantity Unit quantity (defaults to 1)
     * @param string|null $locale Locale for formatting (e.g., 'en-gb', 'fr')
     * @param int $formatterStyle NumberFormatter style constant
     * @return string Formatted price string
     */
    public static function format(
        int $value,
        ?Currency $currency = null,
        int $unitQuantity = 1,
        ?string $locale = null,
        int $formatterStyle = \NumberFormatter::CURRENCY
    ): string {
        $currency = $currency ?? Currency::getDefault();
        $price = new PriceDataType($value, $currency, $unitQuantity);
        
        return $price->formatted($locale, $formatterStyle);
    }

    /**
     * Format a unit price (takes unit_quantity into account).
     * 
     * @param int $value Price value in smallest currency unit
     * @param Currency|null $currency Currency instance
     * @param int $unitQuantity Unit quantity
     * @param string|null $locale Locale for formatting
     * @param int $formatterStyle NumberFormatter style constant
     * @return string Formatted unit price string
     */
    public static function formatUnit(
        int $value,
        ?Currency $currency = null,
        int $unitQuantity = 1,
        ?string $locale = null,
        int $formatterStyle = \NumberFormatter::CURRENCY
    ): string {
        $currency = $currency ?? Currency::getDefault();
        $price = new PriceDataType($value, $currency, $unitQuantity);
        
        return $price->unitFormatted($locale, $formatterStyle);
    }

    /**
     * Get decimal representation of a price.
     * 
     * @param int $value Price value in smallest currency unit
     * @param Currency|null $currency Currency instance
     * @param int $unitQuantity Unit quantity
     * @param bool $rounding Whether to apply rounding
     * @return float Decimal price value
     */
    public static function toDecimal(
        int $value,
        ?Currency $currency = null,
        int $unitQuantity = 1,
        bool $rounding = true
    ): float {
        $currency = $currency ?? Currency::getDefault();
        $price = new PriceDataType($value, $currency, $unitQuantity);
        
        return $price->decimal(rounding: $rounding);
    }

    /**
     * Get unit decimal representation (takes unit_quantity into account).
     * 
     * @param int $value Price value in smallest currency unit
     * @param Currency|null $currency Currency instance
     * @param int $unitQuantity Unit quantity
     * @param bool $rounding Whether to apply rounding
     * @return float Unit decimal price value
     */
    public static function toUnitDecimal(
        int $value,
        ?Currency $currency = null,
        int $unitQuantity = 1,
        bool $rounding = true
    ): float {
        $currency = $currency ?? Currency::getDefault();
        $price = new PriceDataType($value, $currency, $unitQuantity);
        
        return $price->unitDecimal(rounding: $rounding);
    }

    /**
     * Get price for a product variant using Pricing facade.
     * 
     * @param ProductVariant $variant
     * @param int $quantity Quantity (defaults to 1)
     * @param \Lunar\Models\CustomerGroup|array|null $customerGroups Customer groups
     * @return \Lunar\Base\DataTransferObjects\PricingResponse
     */
    public static function getPricing(
        ProductVariant $variant,
        int $quantity = 1,
        $customerGroups = null
    ) {
        $pricing = Pricing::qty($quantity)->for($variant);
        
        if ($customerGroups) {
            if (is_array($customerGroups)) {
                $pricing = $pricing->customerGroups($customerGroups);
            } else {
                $pricing = $pricing->customerGroup($customerGroups);
            }
        }
        
        return $pricing->get();
    }

    /**
     * Get the matched price for a product variant.
     * 
     * @param ProductVariant $variant
     * @param int $quantity Quantity
     * @param \Lunar\Models\CustomerGroup|array|null $customerGroups Customer groups
     * @return PriceDataType|null
     */
    public static function getPrice(
        ProductVariant $variant,
        int $quantity = 1,
        $customerGroups = null
    ): ?PriceDataType {
        $pricing = static::getPricing($variant, $quantity, $customerGroups);
        return $pricing->matched?->price;
    }

    /**
     * Format a Price model's price attribute.
     * 
     * @param Price $priceModel
     * @param string|null $locale Locale for formatting
     * @return string Formatted price string
     */
    public static function formatPriceModel(Price $priceModel, ?string $locale = null): string
    {
        return $priceModel->price->formatted($locale);
    }

    /**
     * Format a PriceDataType instance.
     * 
     * @param PriceDataType $price
     * @param string|null $locale Locale for formatting
     * @param int $formatterStyle NumberFormatter style constant
     * @return string Formatted price string
     */
    public static function formatPriceDataType(
        PriceDataType $price,
        ?string $locale = null,
        int $formatterStyle = \NumberFormatter::CURRENCY
    ): string {
        return $price->formatted($locale, $formatterStyle);
    }

    /**
     * Convert a decimal price to integer (smallest currency unit).
     * 
     * @param float $decimalPrice Decimal price value
     * @param Currency|null $currency Currency instance
     * @return int Price in smallest currency unit
     */
    public static function decimalToInteger(float $decimalPrice, ?Currency $currency = null): int
    {
        $currency = $currency ?? Currency::getDefault();
        $decimalPlaces = $currency->decimal_places ?? 2;
        
        return (int) round($decimalPrice * (10 ** $decimalPlaces));
    }

    /**
     * Convert an integer price to decimal.
     * 
     * @param int $integerPrice Price in smallest currency unit
     * @param Currency|null $currency Currency instance
     * @return float Decimal price value
     */
    public static function integerToDecimal(int $integerPrice, ?Currency $currency = null): float
    {
        $currency = $currency ?? Currency::getDefault();
        $decimalPlaces = $currency->decimal_places ?? 2;
        
        return $integerPrice / (10 ** $decimalPlaces);
    }
}


