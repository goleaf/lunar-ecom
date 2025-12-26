# Product Attributes System - Complete Implementation

This document describes the complete implementation of the flexible, schema-less Product Attributes System with all required features.

## Overview

The Product Attributes System provides a comprehensive, flexible way to manage product attributes with support for multiple attribute types, localization, channel-specific values, variant-level attributes, filtering, searching, sorting, and validation.

## Attribute Types

### Supported Attribute Types

1. **Text** - Single-line text
2. **Long Text** - Multi-line text (rich text support)
3. **Number** - Integer or decimal values
4. **Boolean** - True/false values
5. **Select** - Single select dropdown
6. **Multi-Select** - Multiple select dropdown
7. **Color** - Color picker with hex code and name
8. **Date** - Date picker
9. **File/Media** - File upload and media library integration
10. **Measurement** - Number with unit (kg, cm, inches, etc.)
11. **JSON** - Advanced/custom structured data
12. **TranslatedText** - Multi-language text (localized)

## Attribute Capabilities

### ✅ Attribute Sets Per Product Type

Product types can have different sets of attributes assigned to them. This is managed via Lunar's `mappedAttributes()` relationship.

**Usage:**
```php
use Lunar\Models\ProductType;
use App\Services\AttributeSetService;

$productType = ProductType::find(1);
$service = app(AttributeSetService::class);

// Assign attributes to product type
$service->assignAttributesToProductType($productType, [1, 2, 3, 4]);

// Get all attributes for a product type
$attributes = $service->getAttributesForProductType($productType);

// Check if product type has an attribute
if ($service->productTypeHasAttribute($productType, 'color')) {
    // ...
}
```

### ✅ Required vs Optional Attributes

Attributes can be marked as required or optional via the `required` field.

**Usage:**
```php
use App\Models\Attribute;

// Create a required attribute
$attribute = Attribute::create([
    'handle' => 'sku',
    'required' => true,
    // ...
]);

// Get required attributes for a product type
$required = $service->getRequiredAttributesForProductType($productType);

// Validate required attributes
$missing = $service->validateRequiredAttributes($productType, $attributeData);
if (!empty($missing)) {
    // Handle missing required attributes
}
```

### ✅ Localized Attributes (Per Language)

Attributes can be localized using the `TranslatedText` field type, which supports multiple languages.

**Usage:**
```php
use App\Lunar\Attributes\AttributeHelper;

// Create localized attribute value
$localizedValue = AttributeHelper::translatedText([
    'en' => 'Red',
    'fr' => 'Rouge',
    'de' => 'Rot',
]);

// Get attribute value for specific locale
$value = AttributeHelper::get($product, 'color', 'fr');

// Get with fallback
$value = AttributeHelper::getWithFallback($product, 'color', 'fr', 'en');
```

### ✅ Channel-Specific Attributes

Different attribute values can be set per channel (e.g., different descriptions per market).

**Usage:**
```php
use App\Models\ChannelAttributeValue;
use Lunar\Models\Channel;

$channel = Channel::find(1);
$product = Product::find(1);
$attribute = Attribute::find(1);

// Set channel-specific attribute value
ChannelAttributeValue::updateOrCreate(
    [
        'product_id' => $product->id,
        'channel_id' => $channel->id,
        'attribute_id' => $attribute->id,
    ],
    [
        'value' => 'Channel-specific description',
    ]
);

// Get channel-specific attribute value
$channelValue = ChannelAttributeValue::where('product_id', $product->id)
    ->where('channel_id', $channel->id)
    ->where('attribute_id', $attribute->id)
    ->first();

if ($channelValue) {
    $displayValue = $channelValue->getDisplayValue();
}
```

### ✅ Variant-Level Attributes

Variants can have their own attribute values separate from product-level attributes.

**Usage:**
```php
use App\Models\VariantAttributeValue;
use App\Models\ProductVariant;

$variant = ProductVariant::find(1);
$attribute = Attribute::find(1);

// Set variant-level attribute value
VariantAttributeValue::updateOrCreate(
    [
        'product_variant_id' => $variant->id,
        'attribute_id' => $attribute->id,
    ],
    [
        'value' => 'Variant-specific value',
    ]
);

// Get variant attribute values
$variantAttributes = $variant->variantAttributeValues()->with('attribute')->get();

// Get variant attributes relationship
$attributes = $variant->variantAttributes;
```

### ✅ Filterable Attributes

Attributes can be marked as filterable for use in product filtering.

**Usage:**
```php
use App\Models\Attribute;

// Create filterable attribute
$attribute = Attribute::create([
    'handle' => 'color',
    'filterable' => true,
    // ...
]);

// Get filterable attributes
$filterable = Attribute::filterable()->get();

// Get filterable attributes for product type
$filterable = $service->getFilterableAttributesForProductType($productType);
```

### ✅ Searchable Attributes

Attributes can be marked as searchable for inclusion in search indexing.

**Usage:**
```php
use App\Models\Attribute;

// Create searchable attribute
$attribute = Attribute::create([
    'handle' => 'description',
    'searchable' => true,
    // ...
]);

// Get searchable attributes
$searchable = Attribute::searchable()->get();

// Get searchable attributes for product type
$searchable = $service->getSearchableAttributesForProductType($productType);
```

### ✅ Sortable Attributes

Attributes can be marked as sortable for use in product sorting.

**Usage:**
```php
use App\Models\Attribute;

// Create sortable attribute
$attribute = Attribute::create([
    'handle' => 'price',
    'sortable' => true,
    // ...
]);

// Get sortable attributes
$sortable = Attribute::sortable()->get();

// Get sortable attributes for product type
$sortable = $service->getSortableAttributesForProductType($productType);

// Sort products by attribute
$products = Product::query()
    ->join('lunar_product_attribute_values', 'products.id', '=', 'lunar_product_attribute_values.product_id')
    ->where('lunar_product_attribute_values.attribute_id', $attribute->id)
    ->orderBy('lunar_product_attribute_values.numeric_value')
    ->get();
```

### ✅ Validation Rules Per Attribute

Each attribute can have custom validation rules stored as JSON.

**Usage:**
```php
use App\Models\Attribute;

// Create attribute with validation rules
$attribute = Attribute::create([
    'handle' => 'weight',
    'type' => \Lunar\FieldTypes\Number::class,
    'validation_rules' => [
        'min' => 0,
        'max' => 1000,
        'required' => true,
    ],
    // ...
]);

// Get validation rules
$rules = $attribute->getValidationRules();
// Returns: ['min' => 0, 'max' => 1000, 'required' => true, 'numeric' => true]

// Validate a value
if ($attribute->validateValue(500)) {
    // Value is valid
}
```

## Creating Attributes

### Basic Attribute Creation

```php
use App\Models\Attribute;
use Lunar\Models\AttributeGroup;

$group = AttributeGroup::where('handle', 'specifications')->first();

// Text attribute
$textAttr = Attribute::create([
    'attribute_type' => 'product',
    'attribute_group_id' => $group->id,
    'handle' => 'description',
    'name' => ['en' => 'Description'],
    'type' => \Lunar\FieldTypes\Text::class,
    'required' => false,
    'filterable' => false,
    'searchable' => true,
    'sortable' => false,
    'section' => 'main',
    'position' => 1,
    'configuration' => [],
]);

// Number attribute with unit
$weightAttr = Attribute::create([
    'attribute_type' => 'product',
    'attribute_group_id' => $group->id,
    'handle' => 'weight',
    'name' => ['en' => 'Weight'],
    'type' => \Lunar\FieldTypes\Number::class,
    'unit' => 'kg',
    'required' => false,
    'filterable' => true,
    'searchable' => false,
    'sortable' => true,
    'validation_rules' => [
        'min' => 0,
        'max' => 1000,
    ],
    'section' => 'specifications',
    'position' => 2,
    'configuration' => [],
]);

// Color attribute
$colorAttr = Attribute::create([
    'attribute_type' => 'product',
    'attribute_group_id' => $group->id,
    'handle' => 'color',
    'name' => ['en' => 'Color'],
    'type' => \Lunar\FieldTypes\Text::class, // Or custom Color type
    'required' => false,
    'filterable' => true,
    'searchable' => false,
    'sortable' => false,
    'section' => 'filters',
    'position' => 1,
    'configuration' => [],
]);
```

## Using Attribute Helpers

### Creating Attribute Values

```php
use App\Lunar\Attributes\AttributeHelper;

// Text
$text = AttributeHelper::text('Some text');

// Number
$number = AttributeHelper::number(42);

// Boolean
$boolean = AttributeHelper::boolean(true);

// Color
$color = AttributeHelper::color('#FF0000', 'Red');

// Date
$date = AttributeHelper::date('2024-01-01');
// or
$date = AttributeHelper::date(new \DateTime());

// Measurement
$measurement = AttributeHelper::measurement(5.5, 'kg');

// JSON
$json = AttributeHelper::json(['key' => 'value', 'nested' => ['data' => 123]]);

// Translated text
$translated = AttributeHelper::translatedText([
    'en' => 'English text',
    'fr' => 'French text',
]);
```

## Database Schema

### Attributes Table Extensions

- `sortable` (boolean) - Whether attribute can be used for sorting
- `validation_rules` (JSON) - Custom validation rules per attribute
- `unit` (string) - Unit of measurement (already exists)
- `display_order` (integer) - Order for display (already exists)

### Variant Attribute Values Table

Stores variant-level attribute values:
- `product_variant_id` - Foreign key to product_variants
- `attribute_id` - Foreign key to attributes
- `value` (JSON) - Flexible value storage
- `numeric_value` (decimal) - For numeric attributes
- `text_value` (string) - For text attributes

### Channel Attribute Values Table

Stores channel-specific attribute values:
- `product_id` - Foreign key to products
- `channel_id` - Foreign key to channels
- `attribute_id` - Foreign key to attributes
- `value` (JSON) - Flexible value storage
- `numeric_value` (decimal) - For numeric attributes
- `text_value` (string) - For text attributes

## Models

### Attribute Model

Extended with:
- `sortable` field
- `validation_rules` field
- Methods: `isText()`, `isLongText()`, `isDate()`, `isFile()`, `isMeasurement()`, `isJson()`
- Methods: `getValidationRules()`, `validateValue()`
- Scopes: `scopeSortable()`
- Relationships: `variantAttributeValues()`, `channelAttributeValues()`

### VariantAttributeValue Model

Model for variant-level attribute values with:
- `productVariant()` relationship
- `attribute()` relationship
- `getDisplayValue()` method

### ChannelAttributeValue Model

Model for channel-specific attribute values with:
- `product()` relationship
- `channel()` relationship
- `attribute()` relationship
- `getDisplayValue()` method

## Services

### AttributeSetService

Service for managing attribute sets per product type:
- `assignAttributesToProductType()` - Assign attributes to product type
- `addAttributesToProductType()` - Add attributes without removing existing
- `removeAttributesFromProductType()` - Remove attributes
- `getAttributesForProductType()` - Get all attributes
- `getRequiredAttributesForProductType()` - Get required attributes
- `getFilterableAttributesForProductType()` - Get filterable attributes
- `getSortableAttributesForProductType()` - Get sortable attributes
- `getSearchableAttributesForProductType()` - Get searchable attributes
- `productTypeHasAttribute()` - Check if product type has attribute
- `validateRequiredAttributes()` - Validate required attributes

## Summary

✅ All attribute types implemented (Text, Long Text, Number, Boolean, Select, Color, Date, File, Measurement, JSON)
✅ Attribute sets per product type (via mappedAttributes)
✅ Required vs optional attributes
✅ Localized attributes (TranslatedText)
✅ Channel-specific attributes (ChannelAttributeValue model)
✅ Variant-level attributes (VariantAttributeValue model)
✅ Filterable attributes
✅ Searchable attributes
✅ Sortable attributes
✅ Validation rules per attribute

The Product Attributes System is now complete with all required features and capabilities.


