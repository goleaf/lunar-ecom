<?php

namespace App\Lunar\Variants;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Lunar\Models\Currency;
use Lunar\Models\Price;
use Lunar\Models\ProductOptionValue;

/**
 * Helper class for working with Product Variants.
 * 
 * Provides convenience methods for variant management, pricing, stock, and images.
 */
class VariantHelper
{
    /**
     * Get variant by option combination.
     * 
     * @param Product $product
     * @param array $optionValueIds Array of ProductOptionValue IDs
     * @return ProductVariant|null
     */
    public static function getVariantByOptions(Product $product, array $optionValueIds): ?ProductVariant
    {
        return $product->variants()
            ->whereHas('variantOptions', function ($query) use ($optionValueIds) {
                $query->whereIn('product_option_values.id', $optionValueIds);
            })
            ->withCount(['variantOptions' => function ($query) use ($optionValueIds) {
                $query->whereIn('product_option_values.id', $optionValueIds);
            }])
            ->having('variant_options_count', '=', count($optionValueIds))
            ->first();
    }

    /**
     * Get variant by option handles.
     * 
     * @param Product $product
     * @param array $optionHandles Array of ['option_handle' => 'value_handle']
     * @return ProductVariant|null
     */
    public static function getVariantByHandles(Product $product, array $optionHandles): ?ProductVariant
    {
        $optionValueIds = [];

        foreach ($optionHandles as $optionHandle => $valueHandle) {
            $value = ProductOptionValue::whereHas('option', function ($q) use ($optionHandle) {
                $q->where('handle', $optionHandle);
            })->where('handle', $valueHandle)->first();

            if ($value) {
                $optionValueIds[] = $value->id;
            }
        }

        if (empty($optionValueIds)) {
            return null;
        }

        return static::getVariantByOptions($product, $optionValueIds);
    }

    /**
     * Get variant price for currency.
     * 
     * @param ProductVariant $variant
     * @param Currency|string $currency
     * @param int $quantity
     * @return int|null Price in smallest currency unit
     */
    public static function getPrice(ProductVariant $variant, Currency|string $currency, int $quantity = 1): ?int
    {
        if (is_string($currency)) {
            $currency = Currency::where('code', $currency)->first();
            if (!$currency) {
                return null;
            }
        }

        // Check for price override
        if ($variant->price_override !== null) {
            return $variant->price_override;
        }

        // Use Lunar's pricing system
        $pricing = \Lunar\Facades\Pricing::qty($quantity)->for($variant)->currency($currency)->get();
        return $pricing->matched?->price?->value;
    }

    /**
     * Set variant price.
     * 
     * @param ProductVariant $variant
     * @param int $price Price in smallest currency unit
     * @param Currency|string $currency
     * @param int|null $comparePrice
     * @param int $minQuantity
     * @param int $tier
     * @return Price
     */
    public static function setPrice(
        ProductVariant $variant,
        int $price,
        Currency|string $currency,
        ?int $comparePrice = null,
        int $minQuantity = 1,
        int $tier = 1
    ): Price {
        if (is_string($currency)) {
            $currency = Currency::where('code', $currency)->firstOrFail();
        }

        return Price::updateOrCreate(
            [
                'priceable_type' => ProductVariant::morphName(),
                'priceable_id' => $variant->id,
                'currency_id' => $currency->id,
                'min_quantity' => $minQuantity,
                'tier' => $tier,
            ],
            [
                'price' => $price,
                'compare_price' => $comparePrice,
            ]
        );
    }

    /**
     * Get variant stock status.
     * 
     * @param ProductVariant $variant
     * @return array
     */
    public static function getStockStatus(ProductVariant $variant): array
    {
        return [
            'stock' => $variant->stock,
            'backorder' => $variant->backorder,
            'available' => $variant->isAvailable(),
            'status' => $variant->getStockStatus(),
            'purchasable' => $variant->purchasable,
            'has_sufficient' => function ($qty) use ($variant) {
                return $variant->hasSufficientStock($qty);
            },
        ];
    }

    /**
     * Get variant images.
     * 
     * @param ProductVariant $variant
     * @return Collection
     */
    public static function getImages(ProductVariant $variant): Collection
    {
        return $variant->images()->get()->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb'),
                'medium_url' => $media->getUrl('medium'),
                'large_url' => $media->getUrl('large'),
                'primary' => (bool) $media->pivot?->primary,
            ];
        });
    }

    /**
     * Get variant thumbnail URL.
     * 
     * @param ProductVariant $variant
     * @param string $conversion
     * @return string|null
     */
    public static function getThumbnailUrl(ProductVariant $variant, string $conversion = 'thumb'): ?string
    {
        return $variant->getThumbnailUrl($conversion);
    }

    /**
     * Attach product image to variant.
     * 
     * @param ProductVariant $variant
     * @param int $mediaId Media ID from product
     * @param bool $primary
     * @return void
     */
    public static function attachProductImage(ProductVariant $variant, int $mediaId, bool $primary = false): void
    {
        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($mediaId);
        
        // Ensure media belongs to the product
        if ($media->model_type !== Product::class || $media->model_id !== $variant->product_id) {
            throw new \InvalidArgumentException('Media does not belong to the product.');
        }

        $variant->attachImage($media, $primary);
    }

    /**
     * Get variant display data.
     * 
     * @param ProductVariant $variant
     * @param Currency|string|null $currency
     * @return array
     */
    public static function getDisplayData(ProductVariant $variant, Currency|string $currency = null): array
    {
        if ($currency === null) {
            $currency = \Lunar\Facades\StorefrontSession::getCurrency();
        }

        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'display_name' => $variant->getDisplayName(),
            'option_values' => $variant->getOptionValuesArray(),
            'price' => static::getPrice($variant, $currency),
            'formatted_price' => $currency ? static::formatPrice(static::getPrice($variant, $currency), $currency) : null,
            'compare_price' => static::getComparePrice($variant, $currency),
            'stock' => static::getStockStatus($variant),
            'available' => $variant->isAvailable(),
            'thumbnail_url' => static::getThumbnailUrl($variant),
            'images' => static::getImages($variant),
            'weight' => $variant->weight,
            'formatted_weight' => $variant->formatted_weight,
        ];
    }

    /**
     * Get compare price for variant.
     * 
     * @param ProductVariant $variant
     * @param Currency|string $currency
     * @return int|null
     */
    public static function getComparePrice(ProductVariant $variant, Currency|string $currency): ?int
    {
        if (is_string($currency)) {
            $currency = Currency::where('code', $currency)->first();
            if (!$currency) {
                return null;
            }
        }

        $price = Price::where('priceable_type', ProductVariant::morphName())
            ->where('priceable_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->where('min_quantity', 1)
            ->first();

        return $price?->compare_price;
    }

    /**
     * Format price with currency.
     * 
     * @param int|null $price
     * @param Currency $currency
     * @return string|null
     */
    public static function formatPrice(?int $price, Currency $currency): ?string
    {
        if ($price === null) {
            return null;
        }

        $formatter = new \Lunar\Pricing\DefaultPriceFormatter();
        $priceDataType = new \Lunar\DataTypes\Price(
            $price,
            $currency,
            1
        );

        return $formatter->format($priceDataType);
    }

    /**
     * Get all variants for a product with display data.
     * 
     * @param Product $product
     * @param Currency|string|null $currency
     * @return Collection
     */
    public static function getProductVariants(Product $product, Currency|string $currency = null): Collection
    {
        return $product->variants()
            ->with(['variantOptions.option', 'images', 'prices.currency'])
            ->get()
            ->map(function ($variant) use ($currency) {
                return static::getDisplayData($variant, $currency);
            });
    }

    /**
     * Bulk update variant stock.
     * 
     * @param Collection|array $variants
     * @param int $stock
     * @return int Number of updated variants
     */
    public static function bulkUpdateStock(Collection|array $variants, int $stock): int
    {
        if (is_array($variants)) {
            $variants = collect($variants);
        }

        $variantIds = $variants->pluck('id')->toArray();

        return ProductVariant::whereIn('id', $variantIds)->update(['stock' => $stock]);
    }

    /**
     * Bulk update variant prices.
     * 
     * @param Collection|array $variants
     * @param int $price
     * @param Currency|string $currency
     * @return int Number of updated variants
     */
    public static function bulkUpdatePrices(Collection|array $variants, int $price, Currency|string $currency): int
    {
        if (is_string($currency)) {
            $currency = Currency::where('code', $currency)->firstOrFail();
        }

        if (is_array($variants)) {
            $variants = collect($variants);
        }

        $updated = 0;
        foreach ($variants as $variant) {
            static::setPrice($variant, $price, $currency);
            $updated++;
        }

        return $updated;
    }
}

