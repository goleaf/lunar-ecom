# Product Variants System

This document describes the comprehensive product variant system supporting multiple attributes (size, color, material, style), variant generation, individual pricing, stock management, and variant-specific images.

## Overview

The variant system provides:
- **Multiple Attributes**: Support for size, color, material, style, and any custom attributes
- **Automatic Variant Generation**: Generate all possible combinations from product options
- **Individual Pricing**: Each variant can have its own price, with support for price overrides
- **Stock Management**: Per-variant stock tracking with backorder support
- **Variant-Specific Images**: Each variant can have its own images, with primary image support
- **Comprehensive API**: Full CRUD operations for variant management

## Model Registration

The extended ProductVariant model is registered in `AppServiceProvider`:

```php
\Lunar\Facades\ModelManifest::replace(
    \Lunar\Models\Contracts\ProductVariant::class,
    \App\Models\ProductVariant::class,
);
```

## Product Options Setup

### Creating Product Options

Product options define the attributes available for variants (e.g., Size, Color, Material, Style).

```php
use App\Lunar\Products\ProductOptionHelper;

// Create Size option
$sizeOption = ProductOptionHelper::createOption('Size', 'Size', [
    'Small', 'Medium', 'Large', 'X-Large'
]);

// Create Color option
$colorOption = ProductOptionHelper::createOption('Colour', 'Colour', [
    'Red', 'Blue', 'Green', 'Black', 'White'
]);

// Create Material option
$materialOption = ProductOptionHelper::createOption('Material', 'Material', [
    'Cotton', 'Polyester', 'Wool', 'Silk'
]);

// Create Style option
$styleOption = ProductOptionHelper::createOption('Style', 'Style', [
    'Classic', 'Modern', 'Vintage'
]);
```

### Associating Options with Products

```php
use Lunar\Models\Product;

$product = Product::find(1);

// Associate options with product
$product->productOptions()->attach([
    $sizeOption->id,
    $colorOption->id,
    $materialOption->id,
    $styleOption->id,
]);
```

## Variant Generation

### Automatic Generation

Generate all possible variant combinations from selected options:

```php
use App\Services\VariantGenerator;
use App\Models\Product;

$product = Product::find(1);
$generator = new VariantGenerator();

// Generate variants from all product options
$variants = $generator->generateVariants($product);

// Or specify which options to use
$variants = $generator->generateVariants($product, [
    $sizeOption->id,
    $colorOption->id,
]);

// With default values
$variants = $generator->generateVariants($product, [], [
    'stock' => 100,
    'price' => 2999, // $29.99 in cents
    'currency_id' => 1,
    'purchasable' => 'always',
    'sku_prefix' => 'PROD-001',
]);
```

### Via API

**POST** `/products/{product}/variants/generate`

```json
{
    "option_ids": [1, 2, 3],
    "defaults": {
        "stock": 100,
        "price": 2999,
        "currency_id": 1,
        "purchasable": "always",
        "sku_prefix": "PROD-001"
    }
}
```

### Manual Creation

```php
use App\Http\Requests\StoreVariantRequest;
use App\Models\ProductVariant;

$variant = ProductVariant::create([
    'product_id' => $product->id,
    'sku' => 'PROD-001-RED-L',
    'stock' => 50,
    'purchasable' => 'always',
    'tax_class_id' => 1,
]);

// Attach option values
$variant->variantOptions()->attach([
    $redColorValue->id,
    $largeSizeValue->id,
]);
```

## Variant Attributes

### Extended Fields

The ProductVariant model includes additional fields:

- `price_override`: Variant-specific price override (overrides base pricing)
- `cost_price`: Cost price for the variant
- `compare_at_price`: Compare-at price (for showing discounts)
- `weight`: Weight in grams
- `barcode`: EAN-13 barcode
- `enabled`: Enable/disable variant

### Accessing Variant Data

```php
use App\Lunar\Variants\VariantHelper;

$variant = ProductVariant::find(1);

// Get display data
$data = VariantHelper::getDisplayData($variant);
// Returns: id, sku, display_name, option_values, price, stock, images, etc.

// Get variant by option combination
$variant = VariantHelper::getVariantByOptions($product, [1, 2, 3]);

// Get variant by option handles
$variant = VariantHelper::getVariantByHandles($product, [
    'colour' => 'red',
    'size' => 'large',
]);
```

## Pricing

### Individual Variant Pricing

Each variant can have its own pricing:

```php
use App\Lunar\Variants\VariantHelper;
use Lunar\Models\Currency;

$currency = Currency::where('code', 'USD')->first();

// Set variant price
VariantHelper::setPrice($variant, 2999, $currency, 3999); // $29.99 with $39.99 compare price

// Get variant price
$price = VariantHelper::getPrice($variant, 'USD', 1); // Returns price in cents

// Get formatted price
$formatted = VariantHelper::formatPrice($price, $currency); // "$29.99"
```

### Price Override

Variants can have a price override that takes precedence over base pricing:

```php
$variant->update([
    'price_override' => 2499, // $24.99
]);

// This price will be used instead of the base price
$price = $variant->getEffectivePrice();
```

### Multiple Currency Support

```php
// Set prices for multiple currencies
VariantHelper::setPrice($variant, 2999, 'USD');
VariantHelper::setPrice($variant, 2499, 'EUR');
VariantHelper::setPrice($variant, 2299, 'GBP');
```

### Quantity-Based Pricing

Lunar supports quantity breaks (tiers):

```php
// Base price (quantity 1+)
VariantHelper::setPrice($variant, 2999, $currency, null, 1, 1);

// Bulk price (quantity 10+)
VariantHelper::setPrice($variant, 2499, $currency, null, 10, 2);

// Wholesale price (quantity 50+)
VariantHelper::setPrice($variant, 1999, $currency, null, 50, 3);
```

## Stock Management

### Stock Operations

```php
$variant = ProductVariant::find(1);

// Check stock status
$status = VariantHelper::getStockStatus($variant);
// Returns: stock, backorder, available, status, purchasable, has_sufficient

// Update stock
$variant->update(['stock' => 100]);

// Check availability
$available = $variant->isAvailable();

// Check sufficient stock
$hasStock = $variant->hasSufficientStock(5);

// Decrement stock (handles backorder)
$variant->decrementStock(3);

// Increment stock
$variant->incrementStock(10);
```

### Stock Status

The system provides stock status indicators:

- `in_stock`: Stock > 10
- `low_stock`: Stock > 0 and <= 10
- `backorder`: Stock = 0 but backorder > 0
- `out_of_stock`: Stock = 0 and backorder = 0

### Purchasable Options

- `always`: Always purchasable (even with 0 stock if backorder allowed)
- `in_stock`: Only purchasable when stock > 0
- `never`: Never purchasable

### Bulk Stock Updates

```php
use App\Lunar\Variants\VariantHelper;

$variants = ProductVariant::whereIn('id', [1, 2, 3])->get();

// Update stock for multiple variants
VariantHelper::bulkUpdateStock($variants, 100);
```

## Variant Images

### Attaching Images

Variants can have their own images, separate from product images:

```php
$variant = ProductVariant::find(1);
$product = $variant->product;

// Get product image
$productImage = $product->getFirstMedia('images');

// Attach product image to variant
$variant->attachImage($productImage, true); // true = set as primary

// Or via helper
VariantHelper::attachProductImage($variant, $productImage->id, true);
```

### Managing Variant Images

```php
// Get variant images
$images = VariantHelper::getImages($variant);

// Get thumbnail
$thumbnail = $variant->getThumbnailUrl('thumb');

// Set primary image
$variant->setPrimaryImage($media);

// Detach image
$variant->detachImage($mediaId);

// Check if variant has images
$hasImages = $variant->hasImages();
```

### Via API

**POST** `/variants/{variant}/images`
```json
{
    "media_id": 123,
    "primary": true
}
```

**POST** `/variants/{variant}/images/primary`
```json
{
    "media_id": 123
}
```

**DELETE** `/variants/{variant}/images/{mediaId}`

### Image Fallback

If a variant doesn't have images, it falls back to product images:

```php
$thumbnail = $variant->getThumbnailUrl('thumb');
// Returns variant image if available, otherwise product image
```

## Validation

### FormRequest Classes

**StoreVariantRequest** - For creating variants:

```php
use App\Http\Requests\StoreVariantRequest;

public function store(StoreVariantRequest $request, Product $product)
{
    $variant = ProductVariant::create($request->validated());
    // ...
}
```

**UpdateVariantRequest** - For updating variants:

```php
use App\Http\Requests\UpdateVariantRequest;

public function update(UpdateVariantRequest $request, ProductVariant $variant)
{
    $variant->update($request->validated());
    // ...
}
```

**GenerateVariantsRequest** - For generating variants:

```php
use App\Http\Requests\GenerateVariantsRequest;

public function generate(GenerateVariantsRequest $request, Product $product)
{
    // Generate variants
}
```

### Validation Rules

| Field | Rules |
|-------|-------|
| `sku` | `required`, `string`, `max:255`, `unique` |
| `option_values` | `required`, `array`, `min:1` |
| `stock` | `required`, `integer`, `min:0` |
| `purchasable` | `required`, `in:always,in_stock,never` |
| `price_override` | `nullable`, `integer`, `min:0` |
| `weight` | `nullable`, `integer`, `min:0` |
| `barcode` | `nullable`, `string`, `size:13`, EAN-13 validation |

## API Endpoints

### Variant Management

**GET** `/products/{product}/variants` - List all variants for a product

**GET** `/variants/{variant}` - Get variant details

**POST** `/products/{product}/variants` - Create a new variant

**PUT** `/variants/{variant}` - Update a variant

**DELETE** `/variants/{variant}` - Delete a variant

### Variant Generation

**POST** `/products/{product}/variants/generate` - Generate variants from options

### Stock Management

**POST** `/variants/{variant}/stock` - Update variant stock
```json
{
    "stock": 100
}
```

### Image Management

**POST** `/variants/{variant}/images` - Attach image to variant

**POST** `/variants/{variant}/images/primary` - Set primary image

**DELETE** `/variants/{variant}/images/{mediaId}` - Detach image

## Usage Examples

### Complete Variant Setup

```php
use App\Lunar\Products\ProductOptionHelper;
use App\Services\VariantGenerator;
use App\Models\Product;
use Lunar\Models\Currency;

// 1. Create product options
$sizeOption = ProductOptionHelper::createOption('Size', 'Size', ['S', 'M', 'L', 'XL']);
$colorOption = ProductOptionHelper::createOption('Colour', 'Colour', ['Red', 'Blue', 'Green']);

// 2. Associate with product
$product = Product::find(1);
$product->productOptions()->attach([$sizeOption->id, $colorOption->id]);

// 3. Generate variants
$currency = Currency::where('code', 'USD')->first();
$generator = new VariantGenerator();

$variants = $generator->generateVariants($product, [
    $sizeOption->id,
    $colorOption->id,
], [
    'stock' => 100,
    'price' => 2999,
    'currency_id' => $currency->id,
    'purchasable' => 'always',
    'sku_prefix' => $product->sku,
]);

// 4. Attach images to specific variants
$redVariant = VariantHelper::getVariantByHandles($product, [
    'colour' => 'red',
    'size' => 'large',
]);

if ($redVariant) {
    $redImage = $product->getMedia('images')->first();
    $redVariant->attachImage($redImage, true);
}
```

### Finding Variants

```php
use App\Lunar\Variants\VariantHelper;

// By option combination
$variant = VariantHelper::getVariantByOptions($product, [1, 2, 3]);

// By option handles
$variant = VariantHelper::getVariantByHandles($product, [
    'colour' => 'red',
    'size' => 'large',
]);

// Get all variants with display data
$variants = VariantHelper::getProductVariants($product);
```

### Displaying Variants in Frontend

```php
// In controller
$product = Product::find(1);
$variants = VariantHelper::getProductVariants($product);

return view('products.show', [
    'product' => $product,
    'variants' => $variants,
]);
```

```blade
{{-- In Blade template --}}
@foreach($variants as $variant)
    <div class="variant-option" data-variant-id="{{ $variant['id'] }}">
        <img src="{{ $variant['thumbnail_url'] }}" alt="{{ $variant['display_name'] }}">
        <h3>{{ $variant['display_name'] }}</h3>
        <p>{{ $variant['formatted_price'] }}</p>
        <p>Stock: {{ $variant['stock']['stock'] }}</p>
    </div>
@endforeach
```

## Files

### Models
- `app/Models/ProductVariant.php` - Extended ProductVariant model

### Services
- `app/Services/VariantGenerator.php` - Variant generation service
- `app/Services/ProductVariantService.php` - Variant management service

### Helpers
- `app/Lunar/Variants/VariantHelper.php` - Variant helper methods
- `app/Lunar/Products/ProductOptionHelper.php` - Product option helper

### Controllers
- `app/Http/Controllers/Storefront/VariantController.php` - Variant API controller

### Requests
- `app/Http/Requests/StoreVariantRequest.php` - Create variant validation
- `app/Http/Requests/UpdateVariantRequest.php` - Update variant validation
- `app/Http/Requests/GenerateVariantsRequest.php` - Generate variants validation

### Migrations
- `database/migrations/2022_08_09_100000_create_media_variant_table.php` - Variant images pivot table
- `database/migrations/2025_12_25_090421_add_advanced_fields_to_product_variants_table.php` - Extended variant fields

## Best Practices

1. **Use Product Options**: Define options at the system level, not per-product
2. **Generate Variants**: Use automatic generation for products with multiple attributes
3. **Individual Pricing**: Set variant-specific prices when needed
4. **Stock Management**: Use the built-in stock methods for consistency
5. **Variant Images**: Attach variant-specific images for better UX
6. **Validation**: Always use FormRequest classes for validation
7. **Helper Methods**: Use VariantHelper for common operations
8. **Eager Loading**: Always eager load relationships when fetching multiple variants

## Troubleshooting

### Variants Not Generating

1. Ensure product options are associated with the product
2. Check that option values exist for each option
3. Verify default values are correct
4. Check for duplicate variant combinations

### Images Not Showing

1. Ensure media belongs to the product
2. Check variant image relationships
3. Verify primary image is set
4. Check fallback to product images

### Pricing Issues

1. Verify currency exists
2. Check price override vs base price
3. Ensure pricing is set for the correct currency
4. Check quantity tiers if using bulk pricing

