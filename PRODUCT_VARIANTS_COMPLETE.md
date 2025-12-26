# Product Variants (Sellable Units) - Complete Implementation

This document describes the complete implementation of Product Variants (Sellable Units) with all required fields and logic.

## Overview

The Product Variant system provides comprehensive support for sellable units with all fields and logic as specified in the requirements.

## Variant Fields

### Core Fields (from Lunar base model)
- **SKU**: Unique SKU identifier (configurable rules via validation)
- **Barcode**: EAN/UPC/custom barcode (EAN-13 validated, stored in `barcode` field)
- **Dimensions**: Length, width, height (via Lunar's `dimensions()` method)
- **Stock quantity**: `stock` field (integer)
- **Backorder**: `backorder` field (integer)
- **Availability status**: `purchasable` field (always/in_stock/never)
- **GTIN/MPN/EAN**: Additional identifiers (from base model)

### Extended Fields (custom implementation)
- **Variant name**: `variant_name` field (explicit name, e.g., "Red / XL")
  - Falls back to generated name from option values if not set
  - Access via `getDisplayName()` method
  
- **Attribute combination**: Via `variantOptions()` relationship
  - Supports size, color, material, style, and any custom attributes
  - Multiple attributes per variant
  - Access via `getOptionValuesArray()` method

- **Price (per currency)**: 
  - `price_override`: Variant-specific price override
  - Lunar's pricing system: Multi-currency pricing via `prices` table
  - Access via `getEffectivePrice()` method

- **Compare-at price**: `compare_at_price` field (strike-through price)

- **Cost price**: `cost_price` field (internal cost)

- **Weight**: `weight` field (in grams)

- **Low-stock threshold**: `low_stock_threshold` field
  - Used for stock alerts
  - Defaults to 10 if not set
  - Check via `isLowStock()` method

- **Enable/disable**: `enabled` field (boolean)
  - Disable individual variants
  - Filter via `scopeEnabled()` scope

- **Variant ordering/priority**: `position` field (integer)
  - Sort variants within a product
  - Filter via `scopeOrdered()` scope

### Variant-Specific SEO Fields
- **Meta title**: `meta_title` field
- **Meta description**: `meta_description` field
- **Meta keywords**: `meta_keywords` field
- Access via `getSEOMetaTags()` method
- Falls back to product SEO if not set

## Variant Logic

### Infinite Variants Per Product
✅ **Supported**: No limit on number of variants per product. Variants are linked via `product_id` foreign key.

### Variant Inheritance from Parent Product
✅ **Implemented**: `inheritFromProduct($field)` method
- Checks if variant field is set
- Falls back to product value if not set
- Used for SEO fields, pricing, and other attributes

### Variant-Specific Images
✅ **Implemented**: Via `images()` relationship
- Multiple images per variant
- Primary image flag
- Methods:
  - `attachImage($media, $primary)`: Attach image to variant
  - `setPrimaryImage($media)`: Set primary image
  - `detachImage($mediaId)`: Remove image
  - `getThumbnailUrl($conversion)`: Get thumbnail URL
  - `getVariantImages()`: Get all variant images
  - Falls back to product image if variant has no images

### Variant-Specific SEO
✅ **Implemented**: 
- Fields: `meta_title`, `meta_description`, `meta_keywords`
- Methods:
  - `getMetaTitle()`: Get SEO title (falls back to product)
  - `getMetaDescription()`: Get SEO description (falls back to product)
  - `getMetaKeywords()`: Get SEO keywords (includes variant attributes)
  - `getSEOMetaTags()`: Get all SEO meta tags as array

### Disable Individual Variants
✅ **Implemented**: `enabled` field
- Boolean field (default: true)
- Filter disabled variants via `scopeEnabled()` scope
- Check availability via `isAvailable()` method

### Variant Ordering / Priority
✅ **Implemented**: `position` field
- Integer field (default: 0)
- Sort variants via `scopeOrdered($direction)` scope
- Indexed for performance: `['product_id', 'position']`

## Usage Examples

### Creating a Variant

```php
use App\Models\ProductVariant;

$variant = ProductVariant::create([
    'product_id' => 1,
    'sku' => 'PROD-RED-XL-001',
    'barcode' => '1234567890123', // EAN-13
    'variant_name' => 'Red / XL',
    'price_override' => 2999, // in cents
    'compare_at_price' => 3999,
    'cost_price' => 1500,
    'weight' => 500, // grams
    'stock' => 50,
    'low_stock_threshold' => 10,
    'backorder' => 0,
    'purchasable' => 'in_stock',
    'enabled' => true,
    'position' => 1,
    'meta_title' => 'Red XL T-Shirt - Premium Quality',
    'meta_description' => 'Buy our premium Red XL T-Shirt...',
    'meta_keywords' => 'red, xl, t-shirt, premium',
]);

// Attach option values (size, color, etc.)
$variant->variantOptions()->attach([1, 5]); // Option value IDs
```

### Getting Variant Display Name

```php
// Uses variant_name if set, otherwise generates from option values
$name = $variant->getDisplayName(); // "Red / XL"
```

### Checking Stock Status

```php
// Check if low stock (uses low_stock_threshold)
if ($variant->isLowStock()) {
    // Send alert
}

// Get stock status
$status = $variant->getStockStatus(); // 'in_stock', 'low_stock', 'backorder', 'out_of_stock'
```

### Variant SEO

```php
// Get variant-specific SEO
$metaTags = $variant->getSEOMetaTags();
// Returns: ['title' => ..., 'description' => ..., 'keywords' => ...]

// Individual methods
$title = $variant->getMetaTitle();
$description = $variant->getMetaDescription();
$keywords = $variant->getMetaKeywords();
```

### Variant Ordering

```php
// Get variants ordered by position
$variants = $product->variants()->ordered()->get();

// Get variants ordered descending
$variants = $product->variants()->ordered('desc')->get();
```

### Variant Inheritance

```php
// Inherit field from product if variant doesn't have it
$price = $variant->inheritFromProduct('base_price');
$description = $variant->inheritFromProduct('description');
```

### Variant Images

```php
// Attach image to variant
$variant->attachImage($media, $primary = true);

// Set primary image
$variant->setPrimaryImage($media);

// Get thumbnail URL (falls back to product image)
$thumbnailUrl = $variant->getThumbnailUrl('thumb');

// Get all variant images
$images = $variant->getVariantImages();
```

### Filtering Variants

```php
// Enabled variants only
$variants = ProductVariant::enabled()->get();

// Available variants (enabled + in stock)
$variants = ProductVariant::available()->get();

// Low stock variants
$variants = ProductVariant::lowStock()->get();

// Variants with specific option values
$variants = ProductVariant::withOptionValues([1, 5])->get();

// Ordered variants
$variants = ProductVariant::ordered()->get();
```

## Database Schema

### Migration: `2025_12_25_150300_add_complete_variant_fields_to_product_variants_table.php`

Adds:
- `variant_name` (string, nullable)
- `low_stock_threshold` (unsigned integer, nullable)
- `position` (unsigned integer, default 0, indexed)
- `meta_title` (string, nullable)
- `meta_description` (text, nullable)
- `meta_keywords` (text, nullable)
- Composite index: `['product_id', 'position']`

## Validation Rules

All fields have validation rules in `getValidationRules()`:
- `variant_name`: nullable, string, max 255
- `low_stock_threshold`: nullable, integer, min 0
- `position`: nullable, integer, min 0
- `meta_title`: nullable, string, max 255
- `meta_description`: nullable, string, max 500
- `meta_keywords`: nullable, string, max 500

## Model Registration

The extended ProductVariant model is registered in `AppServiceProvider`:

```php
\Lunar\Facades\ModelManifest::replace(
    \Lunar\Models\Contracts\ProductVariant::class,
    \App\Models\ProductVariant::class,
);
```

## Summary

✅ All variant fields implemented
✅ All variant logic implemented
✅ Variant inheritance supported
✅ Variant-specific images supported
✅ Variant-specific SEO supported
✅ Variant ordering/priority supported
✅ Low-stock threshold supported
✅ Individual variant disable supported

The Product Variant system is now complete with all required fields and functionality.


