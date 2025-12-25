<?php

namespace App\Lunar\Products;

use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Lunar\Facades\Pricing;

/**
 * SEO helper for products.
 * 
 * Provides methods for generating SEO-friendly URLs, meta tags, and structured data
 * including rich snippets for products.
 */
class ProductSEO
{
    /**
     * Get SEO meta tags for product.
     * 
     * @param Product $product
     * @return array
     */
    public static function getMetaTags(Product $product): array
    {
        $defaultUrl = $product->urls->where('default', true)->first();
        $canonicalUrl = $defaultUrl 
            ? route('storefront.products.show', $defaultUrl->slug)
            : url('/products/' . $product->id);

        $metaTitle = $product->translateAttribute('meta_title') 
            ?? $product->translateAttribute('name');

        $metaDescription = $product->translateAttribute('meta_description') 
            ?? static::generateDescription($product);

        $image = static::getProductImage($product);

        return [
            'title' => $metaTitle,
            'description' => $metaDescription,
            'keywords' => static::generateKeywords($product),
            'og:title' => $metaTitle,
            'og:description' => $metaDescription,
            'og:image' => $image,
            'og:type' => 'product',
            'og:url' => $canonicalUrl,
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $metaTitle,
            'twitter:description' => $metaDescription,
            'twitter:image' => $image,
            'canonical' => $canonicalUrl,
        ];
    }

    /**
     * Generate meta description from product.
     * 
     * @param Product $product
     * @return string
     */
    protected static function generateDescription(Product $product): string
    {
        $description = $product->translateAttribute('description');
        
        if ($description) {
            // Strip HTML and limit length
            $text = strip_tags($description);
            return mb_substr($text, 0, 160);
        }

        // Fallback description
        $name = $product->translateAttribute('name');
        $brand = $product->brand ? $product->brand->name : '';
        $brandText = $brand ? " by {$brand}" : '';
        
        return "Buy {$name}{$brandText}. High quality products with fast shipping.";
    }

    /**
     * Generate keywords from product.
     * 
     * @param Product $product
     * @return string
     */
    protected static function generateKeywords(Product $product): string
    {
        $keywords = [$product->translateAttribute('name')];
        
        // Add brand
        if ($product->brand) {
            $keywords[] = $product->brand->name;
        }

        // Add categories
        foreach ($product->categories as $category) {
            $keywords[] = $category->getName();
        }

        // Add tags
        foreach ($product->tags as $tag) {
            $keywords[] = $tag->value;
        }

        return implode(', ', array_unique($keywords));
    }

    /**
     * Get product image URL for meta tags.
     * 
     * @param Product $product
     * @return string|null
     */
    protected static function getProductImage(Product $product): ?string
    {
        $firstMedia = $product->getFirstMedia('images');
        
        if ($firstMedia) {
            return $firstMedia->getUrl('large') ?? $firstMedia->getUrl();
        }

        return null;
    }

    /**
     * Get structured data (JSON-LD) for product with rich snippets.
     * 
     * @param Product $product
     * @return array
     */
    public static function getStructuredData(Product $product): array
    {
        $defaultUrl = $product->urls->where('default', true)->first();
        $productUrl = $defaultUrl 
            ? route('storefront.products.show', $defaultUrl->slug)
            : url('/products/' . $product->id);

        // Get pricing
        $variant = $product->variants->first();
        $price = null;
        $currency = 'USD';
        $availability = 'https://schema.org/OutOfStock';

        if ($variant) {
            $pricing = Pricing::for($variant)->get();
            $priceMatch = $pricing->matched;
            
            if ($priceMatch && $priceMatch->price) {
                $price = $priceMatch->price->decimal;
                $currency = $priceMatch->price->currency->code ?? 'USD';
                
                // Check availability
                if ($variant->stock > 0 || $variant->purchasable) {
                    $availability = 'https://schema.org/InStock';
                }
            }
        }

        // Build structured data
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->translateAttribute('name'),
            'description' => static::generateDescription($product),
            'url' => $productUrl,
            'sku' => $variant?->sku ?? $product->sku ?? null,
            'mpn' => $product->sku ?? null,
            'brand' => $product->brand ? [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ] : null,
            'offers' => [
                '@type' => 'Offer',
                'url' => $productUrl,
                'priceCurrency' => $currency,
                'price' => $price,
                'priceValidUntil' => now()->addYear()->format('Y-m-d'),
                'availability' => $availability,
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];

        // Add images
        $images = [];
        foreach ($product->getMedia('images') as $media) {
            $imageUrl = $media->getUrl('large') ?? $media->getUrl();
            if ($imageUrl) {
                $images[] = $imageUrl;
            }
        }
        if (!empty($images)) {
            $structuredData['image'] = count($images) === 1 ? $images[0] : $images;
        }

        // Add aggregate rating if reviews exist
        if ($product->total_reviews > 0 && $product->average_rating > 0) {
            $structuredData['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $product->average_rating,
                'reviewCount' => (string) $product->total_reviews,
                'bestRating' => '5',
                'worstRating' => '1',
            ];
        }

        // Add category
        if ($product->categories->isNotEmpty()) {
            $category = $product->categories->first();
            $structuredData['category'] = $category->getName();
        }

        // Add additional properties
        if ($product->weight) {
            $structuredData['weight'] = [
                '@type' => 'QuantitativeValue',
                'value' => $product->weight / 1000, // Convert grams to kg
                'unitCode' => 'KGM',
            ];
        }

        if ($product->hasDimensions()) {
            $structuredData['depth'] = [
                '@type' => 'QuantitativeValue',
                'value' => (string) $product->height,
                'unitCode' => 'CMT',
            ];
            $structuredData['width'] = [
                '@type' => 'QuantitativeValue',
                'value' => (string) $product->width,
                'unitCode' => 'CMT',
            ];
            $structuredData['height'] = [
                '@type' => 'QuantitativeValue',
                'value' => (string) $product->length,
                'unitCode' => 'CMT',
            ];
        }

        // Remove null values
        return array_filter($structuredData, fn($value) => $value !== null);
    }

    /**
     * Generate sitemap entry for product.
     * 
     * @param Product $product
     * @return array
     */
    public static function getSitemapEntry(Product $product): array
    {
        $defaultUrl = $product->urls->where('default', true)->first();
        $url = $defaultUrl 
            ? route('storefront.products.show', $defaultUrl->slug)
            : url('/products/' . $product->id);

        return [
            'url' => $url,
            'lastmod' => $product->updated_at->toIso8601String(),
            'changefreq' => 'weekly',
            'priority' => 0.8,
        ];
    }

    /**
     * Get robots meta tag value.
     * 
     * @param Product $product
     * @return string
     */
    public static function getRobotsMeta(Product $product): string
    {
        if ($product->status !== 'published') {
            return 'noindex, nofollow';
        }

        return 'index, follow';
    }
}

