# Product Attributes & Options System

This document describes the flexible product attribute system for filterable properties (brand, color, size, material, features), including attribute groups, attribute values, and frontend filtering.

## Overview

The attribute system provides:
- **Flexible Attributes**: Support for various attribute types (text, number, select, boolean, color)
- **Attribute Groups**: Organize attributes logically (Filters, Specifications, etc.)
- **Filterable Properties**: Mark attributes as filterable for frontend filtering
- **Attribute Values**: Store attribute values separately for efficient querying
- **Frontend Filtering**: UI components for filtering products by attributes
- **Product Counts**: Show product counts for each filter option

## Database Schema

### Attributes Table
Lunar's core attributes table with extensions:
- `filterable`: Boolean for filter availability
- `searchable`: Boolean for search indexing
- `unit`: Unit of measurement (kg, cm, etc.)
- `display_order`: Order for display in filters

### Product Attribute Values Table
Stores attribute values separately for efficient filtering:

```php
Schema::create('lunar_product_attribute_values', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id');
    $table->foreignId('attribute_id');
    $table->json('value'); // Flexible value storage
    $table->decimal('numeric_value', 15, 4)->nullable()->index(); // For numeric attributes
    $table->string('text_value')->nullable()->index(); // For text attributes
    $table->unique(['product_id', 'attribute_id']);
    $table->index(['attribute_id', 'numeric_value']);
    $table->index(['attribute_id', 'text_value']);
});
```

## Creating Attributes

### Basic Attribute Creation

```php
use App\Models\Attribute;
use Lunar\Models\AttributeGroup;

$filterGroup = AttributeGroup::where('handle', 'filters')->first();

// Create a filterable color attribute
$color = Attribute::create([
    'attribute_type' => 'product',
    'attribute_group_id' => $filterGroup->id,
    'position' => 1,
    'name' => ['en' => 'Color'],
    'handle' => 'color',
    'type' => \Lunar\FieldTypes\Text::class,
    'required' => false,
    'searchable' => true,
    'filterable' => true,
    'system' => false,
    'display_order' => 1,
    'section' => 'main',
]);
```

### Using the Seeder

```bash
php artisan lunar:seed-attributes
```

This creates common attributes:
- **Color**: Filterable color attribute
- **Size**: Filterable size attribute
- **Material**: Filterable material attribute
- **Features**: Filterable features attribute
- **Weight**: Numeric weight attribute with unit (kg)
- **Warranty Period**: Numeric warranty period with unit (months)
- **Condition**: Filterable condition attribute

## Assigning Attributes to Products

### Using AttributeService

```php
use App\Services\AttributeService;
use App\Models\Product;

$product = Product::find(1);
$attributeService = app(AttributeService::class);

// Assign attributes by handle
$attributeService->assignAttributesToProduct($product, [
    'color' => 'Red',
    'size' => 'Large',
    'material' => 'Cotton',
    'weight' => 1.5, // Numeric value
    'features' => ['Waterproof', 'Breathable'], // Array for multiselect
]);
```

### Direct Assignment

```php
use App\Models\ProductAttributeValue;
use App\Models\Product;
use App\Models\Attribute;

$product = Product::find(1);
$colorAttribute = Attribute::where('handle', 'color')->first();

ProductAttributeValue::updateOrCreate(
    [
        'product_id' => $product->id,
        'attribute_id' => $colorAttribute->id,
    ],
    [
        'value' => 'Red', // Will auto-populate text_value
    ]
);
```

## Filtering Products by Attributes

### Using ProductAttributeFilterService

```php
use App\Services\ProductAttributeFilterService;
use App\Models\Product;

$filterService = app(ProductAttributeFilterService::class);
$query = Product::query();

// Apply filters (AND logic - product must match ALL)
$filters = [
    'color' => 'Red',
    'size' => ['Large', 'XL'], // Multiple values
    'weight' => ['min' => 1.0, 'max' => 2.0], // Range
];

$query = $filterService->applyFilters($query, $filters, 'and');
$products = $query->get();
```

### OR Logic

```php
// Product must match ANY filter
$query = $filterService->applyFilters($query, $filters, 'or');
```

## Frontend Filtering

### Getting Filterable Attributes

```php
use App\Services\AttributeService;
use App\Lunar\Attributes\AttributeFilterHelper;

$attributeService = app(AttributeService::class);

// Get filterable attributes for a category
$attributes = $attributeService->getFilterableAttributes(null, $categoryId);

// Get grouped attributes
$groupedAttributes = AttributeFilterHelper::getGroupedFilterableAttributes(null, $categoryId);

// Get filter options with product counts
$productQuery = $category->products()->getQuery();
$filterOptions = $attributeService->getFilterOptions($attributes, $productQuery);
```

### In Controllers

```php
use App\Lunar\Attributes\AttributeFilterHelper;
use App\Services\ProductAttributeFilterService;

// Get active filters from request
$activeFilters = AttributeFilterHelper::getActiveFilters($request);

// Apply filters
$filterService = app(ProductAttributeFilterService::class);
$query = $filterService->applyFilters($query, $activeFilters, 'and');
```

### In Views

```blade
{{-- Include attribute filters component --}}
@include('storefront.components.attribute-filters', [
    'groupedAttributes' => $groupedAttributes,
    'activeFilters' => $activeFilters,
    'baseUrl' => route('storefront.products.index')
])
```

## Attribute Types

### Text Attributes
- Single value text
- Used for: Color names, Material names, Features

### Number Attributes
- Numeric values with optional unit
- Supports range filtering (min/max)
- Used for: Weight, Dimensions, Warranty Period

### Boolean Attributes
- Yes/No values
- Used for: Waterproof, Featured, On Sale

### Select/Multiselect Attributes
- Multiple predefined values
- Used for: Size, Condition, Features

### Color Attributes
- Special handling for color display
- Shows color swatches in filters
- Used for: Product Colors

## Filter UI Components

### Numeric Range Filter

```blade
<input type="number" name="weight[min]" value="{{ request('weight.min') }}" placeholder="Min">
<input type="number" name="weight[max]" value="{{ request('weight.max') }}" placeholder="Max">
```

### Color Filter

```blade
@foreach($colorOptions as $option)
    <label>
        <input type="checkbox" name="color[]" value="{{ $option['value'] }}">
        <div class="color-swatch" style="background-color: {{ $option['hex'] }}"></div>
        <span>{{ $option['label'] }} ({{ $option['count'] }})</span>
    </label>
@endforeach
```

### Select Filter

```blade
@foreach($sizeOptions as $option)
    <label>
        <input type="checkbox" name="size[]" value="{{ $option['value'] }}">
        <span>{{ $option['label'] }} ({{ $option['count'] }})</span>
    </label>
@endforeach
```

## Attribute Groups

### Creating Attribute Groups

```php
use Lunar\Models\AttributeGroup;
use Lunar\Models\Product;

$filterGroup = AttributeGroup::create([
    'name' => ['en' => 'Filters'],
    'handle' => 'filters',
    'attributable_type' => Product::class,
    'position' => 1,
]);

$specsGroup = AttributeGroup::create([
    'name' => ['en' => 'Specifications'],
    'handle' => 'specifications',
    'attributable_type' => Product::class,
    'position' => 2,
]);
```

### Grouping Attributes

Attributes are automatically grouped by their `attribute_group_id`. The `AttributeFilterHelper::getGroupedFilterableAttributes()` method groups them for display.

## API Endpoints

### Get Filterable Attributes

**GET** `/api/attribute-filters/filters`
- Query params: `category_id`, `product_type_id`
- Returns: Filterable attributes with options and product counts

### Apply Filters

**POST** `/api/attribute-filters/apply`
- Body: `{ "filters": {...}, "category_id": 1, "logic": "and" }`
- Returns: Filtered products with pagination

### Get Filter Count

**GET** `/api/attribute-filters/count`
- Query params: `attribute_handle`, `value`, `category_id`
- Returns: Product count for specific filter value

## Helper Methods

### AttributeFilterHelper

```php
use App\Lunar\Attributes\AttributeFilterHelper;

// Get grouped filterable attributes
$grouped = AttributeFilterHelper::getGroupedFilterableAttributes($productTypeId, $categoryId);

// Get active filters from request
$activeFilters = AttributeFilterHelper::getActiveFilters($request);

// Check if filter is active
$isActive = AttributeFilterHelper::isFilterActive('color', 'Red', $activeFilters);

// Build filter URL
$url = AttributeFilterHelper::buildFilterUrl($baseUrl, 'color', 'Red', $activeFilters);

// Get filter display name
$name = AttributeFilterHelper::getFilterDisplayName($attribute);

// Format filter value
$formatted = AttributeFilterHelper::formatFilterValue($attribute, $value);
```

### AttributeService

```php
use App\Services\AttributeService;

$service = app(AttributeService::class);

// Assign attributes to product
$service->assignAttributesToProduct($product, ['color' => 'Red']);

// Get product attributes
$attributes = $service->getProductAttributes($product);

// Get filterable attributes
$filterable = $service->getFilterableAttributes($productTypeId, $categoryId);

// Get filter options
$options = $service->getFilterOptions($attributes, $productQuery);
```

## Integration with Product Listings

### Product Index Page

The product index page (`resources/views/storefront/products.index.blade.php`) includes:
- Filter sidebar with attribute filters
- Active filters display
- Product grid with filtered results

### Category Pages

Category pages (`resources/views/storefront/categories/show.blade.php`) include:
- Filter sidebar with category-specific attributes
- Price and brand filters
- Attribute filters grouped by attribute group

## Best Practices

1. **Use Attribute Groups**: Organize attributes logically for better UX
2. **Mark Filterable**: Only mark attributes as `filterable` if they should appear in filters
3. **Use Appropriate Types**: Choose the right attribute type (Number for ranges, Text for selects)
4. **Index Values**: The system automatically indexes `numeric_value` and `text_value` for performance
5. **Cache Filter Options**: Filter options are cached for performance
6. **Product Counts**: Always show product counts for filter options
7. **Active Filters**: Display active filters and allow easy removal
8. **URL Parameters**: Use URL parameters for filter state (shareable, bookmarkable)

## Files

### Models
- `app/Models/Attribute.php` - Extended Attribute model
- `app/Models/ProductAttributeValue.php` - Attribute value storage

### Services
- `app/Services/AttributeService.php` - Attribute management
- `app/Services/ProductAttributeFilterService.php` - Product filtering by attributes

### Helpers
- `app/Lunar/Attributes/AttributeHelper.php` - Basic attribute helpers
- `app/Lunar/Attributes/AttributeFilterHelper.php` - Filter-specific helpers

### Controllers
- `app/Http/Controllers/AttributeFilterController.php` - API endpoints
- `app/Http/Controllers/Storefront/ProductController.php` - Product listing with filters
- `app/Http/Controllers/Storefront/CategoryController.php` - Category pages with filters

### Views
- `resources/views/storefront/components/attribute-filters.blade.php` - Filter component
- `resources/views/storefront/products/index.blade.php` - Product listing with filters
- `resources/views/storefront/categories/show.blade.php` - Category page with filters

### Seeders
- `database/seeders/AttributeSeeder.php` - Common attributes seeder
- `app/Console/Commands/SeedAttributes.php` - Artisan command

### Migrations
- `database/migrations/2025_12_25_092602_create_product_attribute_values_table.php` - Attribute values table
- `database/migrations/2025_12_25_092546_add_filtering_fields_to_attributes_table.php` - Filtering fields

## Example Usage

### Complete Setup

```php
use App\Models\Attribute;
use App\Models\Product;
use App\Services\AttributeService;

// 1. Create attribute (or use seeder)
$color = Attribute::where('handle', 'color')->first();

// 2. Assign to product
$product = Product::find(1);
$service = app(AttributeService::class);
$service->assignAttributesToProduct($product, [
    'color' => 'Red',
    'size' => 'Large',
    'material' => 'Cotton',
]);

// 3. Filter products
$filterService = app(\App\Services\ProductAttributeFilterService::class);
$query = Product::query();
$filters = ['color' => 'Red', 'size' => 'Large'];
$query = $filterService->applyFilters($query, $filters, 'and');
$products = $query->get();
```

### Frontend Filtering

```php
// In controller
use App\Lunar\Attributes\AttributeFilterHelper;
use App\Services\AttributeService;

$attributeService = app(AttributeService::class);
$groupedAttributes = AttributeFilterHelper::getGroupedFilterableAttributes();
$activeFilters = AttributeFilterHelper::getActiveFilters($request);

return view('storefront.products.index', [
    'groupedAttributes' => $groupedAttributes,
    'activeFilters' => $activeFilters,
]);
```

```blade
{{-- In view --}}
@include('storefront.components.attribute-filters', [
    'groupedAttributes' => $groupedAttributes,
    'activeFilters' => $activeFilters,
])
```

