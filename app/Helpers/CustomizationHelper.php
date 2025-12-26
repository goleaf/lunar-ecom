<?php

namespace App\Helpers;

use App\Models\Product;
use App\Services\CustomizationService;

class CustomizationHelper
{
    /**
     * Get customizations for a product.
     *
     * @param  Product  $product
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCustomizations(Product $product)
    {
        $service = app(CustomizationService::class);
        return $service->getProductCustomizations($product);
    }

    /**
     * Check if product has customizations.
     *
     * @param  Product  $product
     * @return bool
     */
    public static function hasCustomizations(Product $product): bool
    {
        return $product->customizations()->count() > 0;
    }

    /**
     * Get customization price for display.
     *
     * @param  float  $price
     * @return string
     */
    public static function formatPrice(float $price): string
    {
        if ($price <= 0) {
            return 'Free';
        }

        $currency = \Lunar\Facades\Currency::getDefault();
        return \Lunar\Facades\Pricing::format($price * 100, $currency);
    }
}


