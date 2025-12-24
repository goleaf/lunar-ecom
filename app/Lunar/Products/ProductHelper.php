<?php

namespace App\Lunar\Products;

use Illuminate\Support\Collection;
use Lunar\Base\DataTransferObjects\PricingResponse;
use Lunar\Facades\Pricing;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Product;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductVariant;

/**
 * Helper class for working with Lunar Products.
 * 
 * Provides convenience methods for managing products, variants, options, and pricing.
 * See: https://docs.lunarphp.com/1.x/reference/products
 */
class ProductHelper
{
    /**
     * Get price for a variant using Pricing facade.
     * 
     * Example usage:
     * ProductHelper::getPrice($variant);
     * ProductHelper::getPrice($variant, 5); // quantity
     * ProductHelper::getPrice($variant, 1, $customerGroup); // with customer group
     * 
     * Returns the Price model instance (not the price property).
     * Access the formatted price via: $price->price->formatted
     * 
     * @param ProductVariant $variant
     * @param int $quantity
     * @param CustomerGroup|Collection|array|null $customerGroups
     * @return \Lunar\Models\Price|null
     */
    public static function getPrice(
        ProductVariant $variant,
        int $quantity = 1,
        CustomerGroup|Collection|array|null $customerGroups = null
    ): ?\Lunar\Models\Price {
        $pricing = Pricing::qty($quantity)->for($variant);
        
        if ($customerGroups) {
            if (is_array($customerGroups) || $customerGroups instanceof Collection) {
                $pricing = $pricing->customerGroups($customerGroups);
            } else {
                $pricing = $pricing->customerGroup($customerGroups);
            }
        }
        
        $pricingResponse = $pricing->get();
        return $pricingResponse->matched ?? null;
    }

    /**
     * Get pricing information for a variant.
     * 
     * Returns full PricingResponse with matched, base, priceBreaks, customerGroupPrices.
     * 
     * @param ProductVariant $variant
     * @param int $quantity
     * @param CustomerGroup|Collection|array|null $customerGroups
     * @return PricingResponse
     */
    public static function getPricing(
        ProductVariant $variant,
        int $quantity = 1,
        CustomerGroup|Collection|array|null $customerGroups = null
    ): PricingResponse {
        $pricing = Pricing::qty($quantity)->for($variant);
        
        if ($customerGroups) {
            if (is_array($customerGroups) || $customerGroups instanceof Collection) {
                $pricing = $pricing->customerGroups($customerGroups);
            } else {
                $pricing = $pricing->customerGroup($customerGroups);
            }
        }
        
        return $pricing->get();
    }

    /**
     * Schedule product for customer groups.
     * 
     * @param Product $product
     * @param CustomerGroup|Collection|array $customerGroups
     * @param \Carbon\Carbon|null $startsAt
     * @return void
     */
    public static function scheduleCustomerGroups(
        Product $product,
        CustomerGroup|Collection|array $customerGroups,
        ?\Carbon\Carbon $startsAt = null
    ): void {
        $product->scheduleCustomerGroup($customerGroups, $startsAt);
    }

    /**
     * Get products for customer groups.
     * 
     * @param CustomerGroup|int|Collection|array $customerGroups
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function forCustomerGroups(CustomerGroup|int|Collection|array $customerGroups)
    {
        return Product::customerGroup($customerGroups);
    }

    /**
     * Create product option with values.
     * 
     * @param string $name
     * @param string|null $label
     * @param array $values Array of value names
     * @return ProductOption
     */
    public static function createProductOption(string $name, ?string $label = null, array $values = []): ProductOption
    {
        $option = ProductOption::create([
            'name' => [
                'en' => $name,
            ],
            'label' => [
                'en' => $label ?? $name,
            ],
        ]);

        if (!empty($values)) {
            $option->values()->createMany(
                collect($values)->map(fn($value) => [
                    'name' => [
                        'en' => $value,
                    ],
                ])->toArray()
            );
        }

        return $option->fresh();
    }

    /**
     * Get all prices for a product (from all variants).
     * 
     * @param Product $product
     * @return Collection
     */
    public static function getAllPrices(Product $product): Collection
    {
        return $product->prices;
    }

    /**
     * Check if product has variants.
     * 
     * @param Product $product
     * @return bool
     */
    public static function hasVariants(Product $product): bool
    {
        return $product->variants()->count() > 1;
    }

    /**
     * Get default variant for a product.
     * 
     * @param Product $product
     * @return ProductVariant|null
     */
    public static function getDefaultVariant(Product $product): ?ProductVariant
    {
        return $product->variants()->first();
    }
}

