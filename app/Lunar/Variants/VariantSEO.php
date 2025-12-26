<?php

namespace App\Lunar\Variants;

use App\Models\ProductVariant;
use Lunar\Facades\Pricing;

/**
 * SEO helper for product variants.
 * 
 * Provides methods for generating SEO-friendly URLs, meta tags, structured data,
 * and social metadata for variants.
 */
class VariantSEO
{
    /**
     * Get SEO meta tags for variant.
     * 
     * @param ProductVariant $variant
     * @param string|null $locale Optional locale
     * @return array
     */
    public static function getMetaTags(ProductVariant $variant, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        
        // Get variant URL
        $variantUrl = static::getVariantUrl($variant);
        
        // Get canonical URL
        $canonicalUrl = static::getCanonicalUrl($variant);
        
        // Get meta title
        $metaTitle = static::getMetaTitle($variant, $locale);
        
        // Get meta description
        $metaDescription = static::getMetaDescription($variant, $locale);
        
        // Get meta keywords
        $metaKeywords = static::getMetaKeywords($variant, $locale);
        
        // Get OpenGraph image
        $ogImage = static::getOpenGraphImage($variant);
        
        // Get robots meta
        $robotsMeta = static::getRobotsMeta($variant);
        
        return [
            'title' => $metaTitle,
            'description' => $metaDescription,
            'keywords' => $metaKeywords,
            'robots' => $robotsMeta,
            'og:title' => static::getOpenGraphTitle($variant, $locale),
            'og:description' => static::getOpenGraphDescription($variant, $locale),
            'og:image' => $ogImage,
            'og:type' => 'product',
            'og:url' => $variantUrl,
            'og:locale' => str_replace('_', '-', $locale),
            'twitter:card' => static::getTwitterCard($variant),
            'twitter:title' => static::getOpenGraphTitle($variant, $locale),
            'twitter:description' => static::getOpenGraphDescription($variant, $locale),
            'twitter:image' => $ogImage,
            'canonical' => $canonicalUrl,
        ];
    }

    /**
     * Get variant-specific URL.
     * 
     * @param ProductVariant $variant
     * @return string
     */
    public static function getVariantUrl(ProductVariant $variant): string
    {
        // If variant has custom URL slug
        if ($variant->url_slug) {
            return route('storefront.variants.show', $variant->url_slug);
        }
        
        // Fallback to product URL with variant parameter
        $product = $variant->product;
        $defaultUrl = $product->urls->where('default', true)->first();
        
        if ($defaultUrl) {
            return route('storefront.products.show', [
                'slug' => $defaultUrl->slug,
                'variant' => $variant->id,
            ]);
        }
        
        return url('/products/' . $product->id . '/variants/' . $variant->id);
    }

    /**
     * Get canonical URL based on inheritance rules.
     * 
     * @param ProductVariant $variant
     * @return string
     */
    public static function getCanonicalUrl(ProductVariant $variant): string
    {
        $inheritance = $variant->canonical_inheritance ?? 'inherit';
        
        switch ($inheritance) {
            case 'override':
                // Use variant-specific canonical URL
                if ($variant->canonical_url) {
                    return $variant->canonical_url;
                }
                // Fall through to variant URL if no canonical_url set
                return static::getVariantUrl($variant);
                
            case 'none':
                // No canonical (rare case)
                return '';
                
            case 'inherit':
            default:
                // Inherit from product
                $product = $variant->product;
                $defaultUrl = $product->urls->where('default', true)->first();
                
                if ($defaultUrl) {
                    return route('storefront.products.show', $defaultUrl->slug);
                }
                
                return url('/products/' . $product->id);
        }
    }

    /**
     * Get meta title for variant.
     * 
     * @param ProductVariant $variant
     * @param string|null $locale
     * @return string
     */
    public static function getMetaTitle(ProductVariant $variant, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        
        // Variant-specific meta title
        if ($variant->meta_title) {
            return $variant->meta_title;
        }
        
        // Fallback to product meta title + variant name
        $product = $variant->product;
        $productMetaTitle = $product->translateAttribute('meta_title', $locale);
        
        if ($productMetaTitle) {
            return $productMetaTitle . ' - ' . $variant->getDisplayName();
        }
        
        // Fallback to product name + variant name
        $productName = $product->translateAttribute('name', $locale);
        $variantName = $variant->getDisplayName();
        
        return $productName . ' - ' . $variantName;
    }

    /**
     * Get meta description for variant.
     * 
     * @param ProductVariant $variant
     * @param string|null $locale
     * @return string
     */
    public static function getMetaDescription(ProductVariant $variant, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        
        // Variant-specific meta description
        if ($variant->meta_description) {
            return $variant->meta_description;
        }
        
        // Fallback to product meta description
        $product = $variant->product;
        $productMetaDescription = $product->translateAttribute('meta_description', $locale);
        
        if ($productMetaDescription) {
            return $productMetaDescription;
        }
        
        // Generate description
        $productName = $product->translateAttribute('name', $locale);
        $variantName = $variant->getDisplayName();
        
        return "Buy {$productName} - {$variantName}. High quality products with fast shipping.";
    }

    /**
     * Get meta keywords for variant.
     * 
     * @param ProductVariant $variant
     * @param string|null $locale
     * @return string
     */
    public static function getMetaKeywords(ProductVariant $variant, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        
        // Variant-specific keywords
        if ($variant->meta_keywords) {
            return $variant->meta_keywords;
        }
        
        // Generate keywords from product and variant attributes
        $keywords = [];
        
        $product = $variant->product;
        $productName = $product->translateAttribute('name', $locale);
        if ($productName) {
            $keywords[] = $productName;
        }
        
        // Add variant display name
        $variantName = $variant->getDisplayName();
        if ($variantName) {
            $keywords[] = $variantName;
        }
        
        // Add option values
        foreach ($variant->variantOptions as $optionValue) {
            $valueName = $optionValue->translateAttribute('name', $locale);
            if ($valueName) {
                $keywords[] = $valueName;
            }
        }
        
        // Add brand
        if ($product->brand) {
            $keywords[] = $product->brand->name;
        }
        
        return implode(', ', array_unique($keywords));
    }

    /**
     * Get robots meta tag.
     * 
     * @param ProductVariant $variant
     * @return string
     */
    public static function getRobotsMeta(ProductVariant $variant): string
    {
        // Variant-specific robots meta
        if ($variant->robots_meta) {
            return $variant->robots_meta;
        }
        
        // Inherit from product
        $product = $variant->product;
        
        // Check product status
        if (method_exists($product, 'isPublished')) {
            if (!$product->isPublished()) {
                return 'noindex, nofollow';
            }
        } elseif ($product->status !== 'published') {
            return 'noindex, nofollow';
        }
        
        // Check variant status
        if ($variant->status !== 'active') {
            return 'noindex, nofollow';
        }
        
        // Check visibility
        if ($variant->visibility === 'hidden') {
            return 'noindex, nofollow';
        }
        
        // Default: index, follow
        return 'index, follow';
    }

    /**
     * Get OpenGraph title.
     * 
     * @param ProductVariant $variant
     * @param string|null $locale
     * @return string
     */
    public static function getOpenGraphTitle(ProductVariant $variant, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        
        // Variant-specific OG title
        if ($variant->og_title) {
            return $variant->og_title;
        }
        
        // Fallback to meta title
        return static::getMetaTitle($variant, $locale);
    }

    /**
     * Get OpenGraph description.
     * 
     * @param ProductVariant $variant
     * @param string|null $locale
     * @return string
     */
    public static function getOpenGraphDescription(ProductVariant $variant, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        
        // Variant-specific OG description
        if ($variant->og_description) {
            return $variant->og_description;
        }
        
        // Fallback to meta description
        return static::getMetaDescription($variant, $locale);
    }

    /**
     * Get OpenGraph image.
     * 
     * @param ProductVariant $variant
     * @return string|null
     */
    public static function getOpenGraphImage(ProductVariant $variant): ?string
    {
        // Variant-specific OG image
        if ($variant->og_image_id) {
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($variant->og_image_id);
            if ($media) {
                return $media->getUrl('large') ?? $media->getUrl();
            }
        }
        
        // Fallback to variant primary image
        $primaryImage = $variant->getPrimaryImage();
        if ($primaryImage && $primaryImage->media) {
            return $primaryImage->media->getUrl('large') ?? $primaryImage->media->getUrl();
        }
        
        // Fallback to product image
        $product = $variant->product;
        $productImage = $product->getFirstMedia('images');
        if ($productImage) {
            return $productImage->getUrl('large') ?? $productImage->getUrl();
        }
        
        return null;
    }

    /**
     * Get Twitter Card type.
     * 
     * @param ProductVariant $variant
     * @return string
     */
    public static function getTwitterCard(ProductVariant $variant): string
    {
        return $variant->twitter_card ?? 'summary_large_image';
    }

    /**
     * Get structured data (JSON-LD) for variant with schema.org Offer.
     * 
     * @param ProductVariant $variant
     * @param string|null $locale
     * @return array
     */
    public static function getStructuredData(ProductVariant $variant, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        
        $product = $variant->product;
        $variantUrl = static::getVariantUrl($variant);
        
        // Get pricing
        $pricing = Pricing::for($variant)->get();
        $priceMatch = $pricing->matched;
        
        $price = null;
        $currency = 'USD';
        $availability = 'https://schema.org/OutOfStock';
        $priceValidUntil = now()->addYear()->format('Y-m-d');
        
        if ($priceMatch && $priceMatch->price) {
            $price = $priceMatch->price->decimal;
            $currency = $priceMatch->price->currency->code ?? 'USD';
            
            // Check availability
            if ($variant->stock > 0 || $variant->purchasable === 'always') {
                $availability = 'https://schema.org/InStock';
            } elseif ($variant->preorder_enabled) {
                $availability = 'https://schema.org/PreOrder';
            } elseif ($variant->backorder > 0) {
                $availability = 'https://schema.org/BackOrder';
            }
        }
        
        // Build Offer structured data
        $offer = [
            '@type' => 'Offer',
            'url' => $variantUrl,
            'priceCurrency' => $currency,
            'price' => $price,
            'priceValidUntil' => $priceValidUntil,
            'availability' => $availability,
            'itemCondition' => 'https://schema.org/NewCondition',
            'sku' => $variant->sku,
        ];
        
        // Add GTIN/EAN/UPC/ISBN if available
        if ($variant->gtin) {
            $offer['gtin'] = $variant->gtin;
        } elseif ($variant->ean) {
            $offer['gtin13'] = $variant->ean;
        } elseif ($variant->upc) {
            $offer['gtin12'] = $variant->upc;
        } elseif ($variant->isbn) {
            $offer['isbn'] = $variant->isbn;
        }
        
        // Add MPN if available
        if ($variant->internal_reference) {
            $offer['mpn'] = $variant->internal_reference;
        }
        
        // Add seller information
        $offer['seller'] = [
            '@type' => 'Organization',
            'name' => config('app.name'),
        ];
        
        // Add shipping details if available
        if ($variant->weight) {
            $offer['shippingDetails'] = [
                '@type' => 'OfferShippingDetails',
                'shippingRate' => [
                    '@type' => 'MonetaryAmount',
                    'value' => '0',
                    'currency' => $currency,
                ],
                'shippingDestination' => [
                    '@type' => 'DefinedRegion',
                    'addressCountry' => 'US',
                ],
            ];
        }
        
        // Build Product structured data with variant-specific Offer
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->translateAttribute('name', $locale) . ' - ' . $variant->getDisplayName(),
            'description' => static::getMetaDescription($variant, $locale),
            'url' => $variantUrl,
            'inLanguage' => str_replace('_', '-', $locale),
            'sku' => $variant->sku,
            'offers' => $offer,
        ];
        
        // Add brand
        if ($product->brand) {
            $structuredData['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ];
        }
        
        // Add variant images
        $images = [];
        $variantImages = $variant->getImages();
        foreach ($variantImages as $variantMedia) {
            if ($variantMedia->media) {
                $imageUrl = $variantMedia->media->getUrl('large') ?? $variantMedia->media->getUrl();
                if ($imageUrl) {
                    $images[] = $imageUrl;
                }
            }
        }
        
        // Fallback to product images
        if (empty($images)) {
            foreach ($product->getMedia('images') as $media) {
                $imageUrl = $media->getUrl('large') ?? $media->getUrl();
                if ($imageUrl) {
                    $images[] = $imageUrl;
                }
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
        
        // Add variant-specific properties
        if ($variant->weight) {
            $structuredData['weight'] = [
                '@type' => 'QuantitativeValue',
                'value' => $variant->weight / 1000, // Convert grams to kg
                'unitCode' => 'KGM',
            ];
        }
        
        if ($variant->dimensions) {
            $dimensions = $variant->dimensions;
            if (isset($dimensions['length']) && isset($dimensions['width']) && isset($dimensions['height'])) {
                $structuredData['depth'] = [
                    '@type' => 'QuantitativeValue',
                    'value' => (string) $dimensions['height'],
                    'unitCode' => 'CMT',
                ];
                $structuredData['width'] = [
                    '@type' => 'QuantitativeValue',
                    'value' => (string) $dimensions['width'],
                    'unitCode' => 'CMT',
                ];
                $structuredData['height'] = [
                    '@type' => 'QuantitativeValue',
                    'value' => (string) $dimensions['length'],
                    'unitCode' => 'CMT',
                ];
            }
        }
        
        // Remove null values
        return array_filter($structuredData, fn($value) => $value !== null);
    }

    /**
     * Generate sitemap entry for variant.
     * 
     * @param ProductVariant $variant
     * @return array|null Returns null if variant should not be indexed
     */
    public static function getSitemapEntry(ProductVariant $variant): ?array
    {
        // Check if variant should be indexed
        $robotsMeta = static::getRobotsMeta($variant);
        if (str_contains($robotsMeta, 'noindex')) {
            return null;
        }
        
        // Only include variants with custom URLs or if configured to include all
        if (!$variant->url_slug && !config('lunar.variants.include_all_in_sitemap', false)) {
            return null;
        }
        
        $variantUrl = static::getVariantUrl($variant);
        
        return [
            'url' => $variantUrl,
            'lastmod' => $variant->updated_at->toIso8601String(),
            'changefreq' => 'weekly',
            'priority' => 0.7, // Slightly lower than product priority
        ];
    }
}
