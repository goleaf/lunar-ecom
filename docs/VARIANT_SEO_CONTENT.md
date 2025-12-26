# Variant SEO & Content

Complete SEO and content system for product variants.

## Overview

Even variants can rank! This system provides comprehensive SEO features for variants:

1. **Variant-specific URL** (optional)
2. **Canonical inheritance rules**
3. **Variant meta title / description**
4. **Structured data** (schema.org Offer)
5. **OpenGraph / social metadata**
6. **Indexing rules** (index / noindex)

## Variant-Specific URL

### Optional URL Slug

Variants can have their own URL slug for SEO purposes:

```php
// Set variant-specific URL slug
$variant->update([
    'url_slug' => 'red-t-shirt-xl',
]);

// Get variant URL
$variantUrl = $variant->getVariantUrl();
// Returns: /variants/red-t-shirt-xl

// If no URL slug, falls back to product URL with variant parameter
// Returns: /products/t-shirt?variant=123
```

### URL Generation

```php
use App\Lunar\Variants\VariantSEO;

// Get variant URL
$url = VariantSEO::getVariantUrl($variant);

// Variant with custom slug: /variants/red-t-shirt-xl
// Variant without slug: /products/t-shirt?variant=123
```

## Canonical Inheritance Rules

### Inheritance Options

1. **`inherit`** (default) - Inherit canonical from product
2. **`override`** - Use variant-specific canonical URL
3. **`none`** - No canonical URL (rare)

```php
// Set canonical inheritance
$variant->update([
    'canonical_inheritance' => 'inherit', // inherit, override, none
    'canonical_url' => 'https://example.com/products/t-shirt-red-xl', // Only used if override
]);

// Get canonical URL
$canonicalUrl = $variant->getCanonicalUrl();

// Inheritance logic:
// - inherit: Returns product canonical URL
// - override: Returns variant->canonical_url or variant URL
// - none: Returns empty string
```

### Canonical URL Logic

```php
use App\Lunar\Variants\VariantSEO;

$canonicalUrl = VariantSEO::getCanonicalUrl($variant);

// inherit (default):
// Returns product canonical URL

// override:
// Returns variant->canonical_url if set
// Otherwise returns variant URL

// none:
// Returns empty string
```

## Variant Meta Title / Description

### Meta Tags

```php
// Set variant-specific meta tags
$variant->update([
    'meta_title' => 'Red T-Shirt XL - Premium Cotton',
    'meta_description' => 'Buy our premium red t-shirt in size XL. Made from 100% organic cotton.',
    'meta_keywords' => 'red t-shirt, xl, cotton, organic',
]);

// Get meta tags
$metaTags = $variant->getSEOMetaTags('en');

// Returns:
// [
//     'title' => 'Red T-Shirt XL - Premium Cotton',
//     'description' => 'Buy our premium red t-shirt...',
//     'keywords' => 'red t-shirt, xl, cotton, organic',
//     'robots' => 'index, follow',
//     'og:title' => '...',
//     'og:description' => '...',
//     'canonical' => '...',
//     ...
// ]
```

### Fallback Logic

```php
// Meta title fallback:
// 1. variant->meta_title
// 2. product->meta_title + variant name
// 3. product->name + variant name

// Meta description fallback:
// 1. variant->meta_description
// 2. product->meta_description
// 3. Generated description

// Meta keywords fallback:
// 1. variant->meta_keywords
// 2. Generated from product name + variant attributes
```

## Structured Data (Schema.org Offer)

### Variant-Specific Offer

Each variant generates its own schema.org Offer with variant-specific pricing and availability:

```php
use App\Lunar\Variants\VariantSEO;

// Get structured data
$structuredData = $variant->getStructuredData('en');

// Returns schema.org Product with variant-specific Offer:
// {
//     "@context": "https://schema.org",
//     "@type": "Product",
//     "name": "T-Shirt - Red / XL",
//     "sku": "TSH-RED-XL",
//     "offers": {
//         "@type": "Offer",
//         "url": "https://example.com/variants/red-t-shirt-xl",
//         "priceCurrency": "USD",
//         "price": "29.99",
//         "priceValidUntil": "2026-12-26",
//         "availability": "https://schema.org/InStock",
//         "sku": "TSH-RED-XL",
//         "gtin": "1234567890123",
//         "seller": {
//             "@type": "Organization",
//             "name": "My Store"
//         }
//     }
// }
```

### Offer Properties

- **Price**: Variant-specific pricing
- **Availability**: Based on stock status (InStock, OutOfStock, PreOrder, BackOrder)
- **SKU**: Variant SKU
- **GTIN/EAN/UPC/ISBN**: Barcode information
- **MPN**: Internal reference code
- **Seller**: Organization information
- **Shipping Details**: Weight-based shipping

## OpenGraph / Social Metadata

### OpenGraph Tags

```php
// Set OpenGraph metadata
$variant->update([
    'og_title' => 'Red T-Shirt XL - Premium Quality',
    'og_description' => 'Discover our premium red t-shirt in size XL...',
    'og_image_id' => $mediaId, // Media ID for OG image
    'twitter_card' => 'summary_large_image', // summary, summary_large_image, app, player
]);

// Get OpenGraph tags
$metaTags = $variant->getSEOMetaTags();

// Returns:
// [
//     'og:title' => 'Red T-Shirt XL - Premium Quality',
//     'og:description' => 'Discover our premium red t-shirt...',
//     'og:image' => 'https://example.com/media/large/image.jpg',
//     'og:type' => 'product',
//     'og:url' => 'https://example.com/variants/red-t-shirt-xl',
//     'og:locale' => 'en',
//     'twitter:card' => 'summary_large_image',
//     'twitter:title' => '...',
//     'twitter:description' => '...',
//     'twitter:image' => '...',
// ]
```

### Image Fallback

```php
// OpenGraph image fallback order:
// 1. variant->og_image_id (if set)
// 2. variant primary image
// 3. product primary image
```

## Indexing Rules (Index / Noindex)

### Robots Meta

```php
// Set robots meta
$variant->update([
    'robots_meta' => 'index, follow', // or 'noindex, nofollow', etc.
]);

// Get robots meta
$robotsMeta = $variant->getRobotsMeta();

// Default logic:
// - If variant->robots_meta is set, use it
// - If product status !== 'published', return 'noindex, nofollow'
// - If variant status !== 'active', return 'noindex, nofollow'
// - If variant visibility === 'hidden', return 'noindex, nofollow'
// - Otherwise, return 'index, follow'
```

### Common Robots Meta Values

- `index, follow` - Index and follow links (default for active variants)
- `noindex, nofollow` - Don't index, don't follow
- `noindex, follow` - Don't index, but follow links
- `index, nofollow` - Index, but don't follow links

## Usage Examples

### Complete SEO Setup

```php
// Set up variant SEO
$variant->update([
    // URL
    'url_slug' => 'red-t-shirt-xl',
    
    // Canonical
    'canonical_inheritance' => 'override',
    'canonical_url' => 'https://example.com/products/t-shirt-red-xl',
    
    // Meta tags
    'meta_title' => 'Red T-Shirt XL - Premium Cotton',
    'meta_description' => 'Buy our premium red t-shirt in size XL...',
    'meta_keywords' => 'red t-shirt, xl, cotton',
    
    // Robots
    'robots_meta' => 'index, follow',
    
    // OpenGraph
    'og_title' => 'Red T-Shirt XL - Premium Quality',
    'og_description' => 'Discover our premium red t-shirt...',
    'og_image_id' => $mediaId,
    'twitter_card' => 'summary_large_image',
]);
```

### Frontend Usage

```blade
@php
    use App\Lunar\Variants\VariantSEO;
    $metaTags = VariantSEO::getMetaTags($variant);
    $structuredData = VariantSEO::getStructuredData($variant);
@endphp

<!DOCTYPE html>
<html>
<head>
    <title>{{ $metaTags['title'] }}</title>
    
    {{-- Meta Tags --}}
    <meta name="description" content="{{ $metaTags['description'] }}">
    <meta name="keywords" content="{{ $metaTags['keywords'] }}">
    <meta name="robots" content="{{ $metaTags['robots'] }}">
    
    {{-- OpenGraph --}}
    <meta property="og:title" content="{{ $metaTags['og:title'] }}">
    <meta property="og:description" content="{{ $metaTags['og:description'] }}">
    @if($metaTags['og:image'])
        <meta property="og:image" content="{{ $metaTags['og:image'] }}">
    @endif
    <meta property="og:url" content="{{ $metaTags['og:url'] }}">
    <meta property="og:type" content="{{ $metaTags['og:type'] }}">
    
    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ $metaTags['twitter:card'] }}">
    <meta name="twitter:title" content="{{ $metaTags['twitter:title'] }}">
    <meta name="twitter:description" content="{{ $metaTags['twitter:description'] }}">
    @if($metaTags['twitter:image'])
        <meta name="twitter:image" content="{{ $metaTags['twitter:image'] }}">
    @endif
    
    {{-- Canonical --}}
    @if($metaTags['canonical'])
        <link rel="canonical" href="{{ $metaTags['canonical'] }}">
    @endif
    
    {{-- Structured Data --}}
    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
</head>
<body>
    {{-- Variant content --}}
</body>
</html>
```

### Sitemap Integration

```php
use App\Lunar\Variants\VariantSEO;

// Get sitemap entry for variant
$sitemapEntry = VariantSEO::getSitemapEntry($variant);

// Returns null if variant should not be indexed
// Returns array with url, lastmod, changefreq, priority if should be indexed

// Only variants with url_slug or configured to include all are included
```

## Best Practices

1. **Use variant-specific URLs** for important variants
2. **Set canonical inheritance** based on SEO strategy
3. **Write unique meta titles** for each variant
4. **Include variant attributes** in meta descriptions
5. **Set robots meta** for variants that shouldn't be indexed
6. **Use variant-specific OG images** for better social sharing
7. **Include structured data** for rich snippets
8. **Test canonical URLs** to avoid duplicate content
9. **Monitor indexing** with Google Search Console
10. **Use consistent URL structure** across variants

## Notes

- **Variant URLs**: Optional, falls back to product URL with variant parameter
- **Canonical inheritance**: Defaults to inheriting from product
- **Structured data**: Each variant generates its own Offer
- **Robots meta**: Inherits from product if not set
- **OpenGraph**: Falls back to variant/product images
- **Sitemap**: Only includes variants with custom URLs (configurable)

