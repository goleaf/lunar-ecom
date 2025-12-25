# Product Model Extension

This document describes the extended Product model with custom attributes, validation rules, and helper methods.

## Overview

The `App\Models\Product` extends Lunar's base `Product` model with additional custom attributes:

- **SKU**: Unique product identifier
- **Barcode**: EAN-13 barcode with validation
- **Weight**: Product weight in grams
- **Dimensions**: Length, width, height in centimeters
- **Manufacturer**: Manufacturer name
- **Warranty Period**: Warranty duration in months
- **Condition**: Product condition (new/refurbished/used)
- **Origin Country**: ISO 2-character country code
- **Custom Meta**: JSON field for unlimited custom fields

## Model Registration

The extended Product model is registered in `AppServiceProvider`:

```php
\Lunar\Facades\ModelManifest::replace(
    \Lunar\Models\Contracts\Product::class,
    \App\Models\Product::class,
);
```

## Database Schema

The custom attributes are added via migration:

```php
Schema::table('lunar_products', function (Blueprint $table) {
    $table->string('sku')->unique()->nullable();
    $table->string('barcode', 13)->nullable()->index();
    $table->unsignedInteger('weight')->nullable();
    $table->decimal('length', 8, 2)->nullable();
    $table->decimal('width', 8, 2)->nullable();
    $table->decimal('height', 8, 2)->nullable();
    $table->string('manufacturer_name')->nullable()->index();
    $table->unsignedSmallInteger('warranty_period')->nullable();
    $table->enum('condition', ['new', 'refurbished', 'used'])->nullable()->index();
    $table->string('origin_country', 2)->nullable()->index();
    $table->json('custom_meta')->nullable();
});
```

## Usage

### Creating Products

```php
use App\Models\Product;

$product = Product::create([
    'product_type_id' => 1,
    'status' => 'published',
    'sku' => 'PROD-001',
    'barcode' => '1234567890123',
    'weight' => 500, // grams
    'length' => 10.5,
    'width' => 8.0,
    'height' => 5.5,
    'manufacturer_name' => 'Acme Corp',
    'warranty_period' => 24, // months
    'condition' => 'new',
    'origin_country' => 'US',
    'custom_meta' => [
        'material' => 'Plastic',
        'color_family' => 'Blue',
    ],
]);
```

### Accessing Custom Attributes

```php
$product = Product::find(1);

// Direct access
$sku = $product->sku;
$weight = $product->weight;
$dimensions = $product->formatted_dimensions; // "10.50 × 8.00 × 5.50 cm"

// Custom meta
$material = $product->getCustomMeta('material');
$product->setCustomMeta('color', 'Red');
$product->removeCustomMeta('old_field');
```

### Formatted Attributes

The model provides formatted accessors:

```php
$product->formatted_weight; // "500 g"
$product->formatted_dimensions; // "10.50 × 8.00 × 5.50 cm"
$product->formatted_warranty_period; // "2 years"
$product->formatted_volume; // "462.00 cm³"
$product->volume; // 462.0 (float)
```

### Weight Conversions

```php
$product->getWeightInKg(); // 0.5 (kilograms)
$product->getWeightInLbs(); // 1.102 (pounds)
```

### Dimension Conversions

```php
$product->getDimensionsInUnit('cm'); // ['length' => 10.5, 'width' => 8.0, 'height' => 5.5]
$product->getDimensionsInUnit('in'); // ['length' => 4.13, 'width' => 3.15, 'height' => 2.17]
$product->getFormattedDimensionsInUnit('in'); // "4.13 × 3.15 × 2.17 in"
```

## Validation

### Using FormRequest Classes

**StoreProductRequest** - For creating new products:

```php
use App\Http\Requests\StoreProductRequest;

public function store(StoreProductRequest $request)
{
    $product = Product::create($request->validated());
    return response()->json($product);
}
```

**UpdateProductRequest** - For updating existing products:

```php
use App\Http\Requests\UpdateProductRequest;

public function update(UpdateProductRequest $request, Product $product)
{
    $product->update($request->validated());
    return response()->json($product);
}
```

### Manual Validation

```php
use App\Models\Product;

$rules = Product::getValidationRules();
$messages = Product::getValidationMessages();

$validated = $request->validate($rules, $messages);
```

### Validation Rules

| Field | Rules |
|-------|-------|
| `sku` | `nullable`, `string`, `max:255`, `unique:lunar_products,sku` |
| `barcode` | `nullable`, `string`, `size:13`, EAN-13 format validation |
| `weight` | `nullable`, `integer`, `min:0` |
| `length` | `nullable`, `numeric`, `min:0`, `max:999999.99` |
| `width` | `nullable`, `numeric`, `min:0`, `max:999999.99` |
| `height` | `nullable`, `numeric`, `min:0`, `max:999999.99` |
| `manufacturer_name` | `nullable`, `string`, `max:255` |
| `warranty_period` | `nullable`, `integer`, `min:0`, `max:65535` |
| `condition` | `nullable`, `in:new,refurbished,used` |
| `origin_country` | `nullable`, `string`, `size:2` |
| `custom_meta` | `nullable`, `array` |

### EAN-13 Barcode Validation

The model includes automatic EAN-13 barcode validation:

```php
$product = new Product();
$product->barcode = '1234567890123';

if ($product->validateEan13($product->barcode)) {
    // Valid EAN-13 barcode
}
```

The validation:
- Removes non-digit characters
- Checks for exactly 13 digits
- Validates the check digit using the EAN-13 algorithm

## Query Scopes

### Filtering by Custom Attributes

```php
// By SKU
$products = Product::bySku('PROD-001')->get();

// By barcode
$products = Product::byBarcode('1234567890123')->get();

// By manufacturer
$products = Product::byManufacturer('Acme Corp')->get();

// By condition
$products = Product::byCondition('new')->get();

// By origin country
$products = Product::byOriginCountry('US')->get();

// With warranty
$products = Product::withWarranty()->get();

// By weight range
$products = Product::byWeightRange(100, 1000)->get();

// By warranty period range
$products = Product::byWarrantyPeriodRange(12, 36)->get();

// By volume range
$products = Product::byVolumeRange(100.0, 1000.0)->get();

// With complete shipping info
$products = Product::withShippingInfo()->get();

// Search custom attributes
$products = Product::searchCustomAttributes('Acme')->get();
```

## Custom Meta Fields

The `custom_meta` JSON field allows storing unlimited custom fields:

```php
$product = Product::find(1);

// Get a meta field
$material = $product->getCustomMeta('material');

// Set a meta field
$product->setCustomMeta('color', 'Blue');
$product->save();

// Check if meta field exists
if ($product->hasCustomMeta('material')) {
    // ...
}

// Get all meta fields
$allMeta = $product->getAllCustomMeta();

// Remove a meta field
$product->removeCustomMeta('old_field');
$product->save();
```

## Helper Methods

### Checking Attributes

```php
$product->hasDimensions(); // true if length, width, height are set
$product->hasWeight(); // true if weight > 0
$product->hasWarranty(); // true if warranty_period > 0
```

### Computed Properties

```php
$product->volume; // Calculated: length × width × height
$product->formatted_volume; // "462.00 cm³"
$product->formatted_weight; // "500 g"
$product->formatted_dimensions; // "10.50 × 8.00 × 5.50 cm"
$product->formatted_warranty_period; // "2 years"
```

## Relationships

### Manufacturer Relationship

The model provides a convenience relationship to match manufacturer_name with Brand names:

```php
$product = Product::with('manufacturer')->find(1);
$brand = $product->manufacturer; // Returns Brand model if manufacturer_name matches
```

Note: This is a convenience method. The `manufacturer_name` field is stored as a string, not a foreign key.

## Data Normalization

The FormRequest classes automatically normalize data:

- **Barcode**: Removes spaces and dashes
- **SKU**: Converts to uppercase and trims
- **Origin Country**: Converts to uppercase

## Example Usage in Controller

```php
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());
        
        // Add custom meta
        $product->setCustomMeta('internal_notes', 'Special handling required');
        $product->save();
        
        return response()->json([
            'product' => $product,
            'formatted' => [
                'weight' => $product->formatted_weight,
                'dimensions' => $product->formatted_dimensions,
                'warranty' => $product->formatted_warranty_period,
            ],
        ]);
    }
    
    public function show(Product $product)
    {
        return response()->json([
            'product' => $product,
            'shipping' => [
                'weight_kg' => $product->getWeightInKg(),
                'weight_lbs' => $product->getWeightInLbs(),
                'dimensions_cm' => $product->getDimensionsInUnit('cm'),
                'dimensions_in' => $product->getDimensionsInUnit('in'),
                'volume' => $product->formatted_volume,
            ],
            'meta' => $product->getAllCustomMeta(),
        ]);
    }
}
```

## Files

- `app/Models/Product.php` - Extended Product model
- `app/Http/Requests/StoreProductRequest.php` - Validation for creating products
- `app/Http/Requests/UpdateProductRequest.php` - Validation for updating products
- `database/migrations/2025_12_25_090058_add_custom_attributes_to_products_table.php` - Migration
- `app/Providers/AppServiceProvider.php` - Model registration

## Best Practices

1. **Always use FormRequest classes** for validation in controllers
2. **Normalize data** before saving (handled automatically by FormRequest)
3. **Use query scopes** for filtering instead of manual where clauses
4. **Use helper methods** for formatted output instead of manual formatting
5. **Store custom data** in `custom_meta` for flexibility
6. **Validate barcodes** using the built-in EAN-13 validation
7. **Use relationships** when possible, but manufacturer_name is stored as string for simplicity

