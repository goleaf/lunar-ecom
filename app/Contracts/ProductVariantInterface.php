<?php

namespace App\Contracts;

use App\Models\ProductVariant;
use Lunar\Models\Currency;
use Lunar\Models\Price;

interface ProductVariantInterface
{
    /**
     * Update stock quantity
     */
    public function updateStock(ProductVariant $variant, int $quantity): ProductVariant;

    /**
     * Check if variant is available
     */
    public function isAvailable(ProductVariant $variant): bool;

    /**
     * Get price for currency
     */
    public function getPrice(ProductVariant $variant, Currency $currency): ?Price;
}