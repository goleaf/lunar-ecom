# Attribute Values

Complete attribute value system with typed storage, per-locale/channel values, variant overrides, fallback logic, and change history.

## Overview

The Attribute Values System provides:

- ✅ **Typed storage** - Different storage types for different attribute types
- ✅ **Per-locale values** - Support for multiple languages
- ✅ **Per-channel values** - Different values per channel
- ✅ **Variant overrides** - Variants can override product values
- ✅ **Fallback logic** - Fallback to default/product values when variant doesn't have value
- ✅ **Change history** - Track all changes to attribute values

## Architecture

### Value Storage Types

- **Product Values** - Product-level attribute values
- **Variant Values** - Variant-level attribute values (overrides)
- **Channel Values** - Channel-specific attribute values

### Fallback Priority

1. **Variant value** (if variant provided)
2. **Channel value** (if channel provided)
3. **Product value**
4. **Default value** (from attribute definition)

## Usage

### Set Product Attribute Value

```php
use App\Services\AttributeValueService;

$service = app(AttributeValueService::class);

// Set product value
$service->setProductValue(
    product: $product,
    attribute: 'description', // handle, code, or Attribute instance
    value: 'Product description',
    locale: 'en',
    isOverride: false
);
```

### Set Variant Attribute Value (Override)

```php
// Set variant value (overrides product value)
$service->setVariantValue(
    variant: $variant,
    attribute: 'color',
    value: 'red',
    locale: 'en',
    isOverride: true // Variants are overrides by default
);
```

### Set Channel Attribute Value

```php
// Set channel-specific value
$service->setChannelValue(
    product: $product,
    channel: $channel,
    attribute: 'description',
    value: 'Channel-specific description',
    locale: 'en',
    isOverride: false
);
```

### Get Value with Fallback

```php
// Get value with automatic fallback
$value = $service->getValue(
    product: $product,
    attribute: 'description',
    variant: $variant, // Optional
    channel: $channel, // Optional
    locale: 'en' // Optional, defaults to current locale
);

// Returns value in priority order:
// 1. Variant value (if variant provided)
// 2. Channel value (if channel provided)
// 3. Product value
// 4. Default value (from attribute definition)
```

## Typed Storage

### Text Values

```php
// Simple text
$service->setProductValue($product, 'name', 'Product Name');

// Localized text
$service->setProductValue($product, 'description', [
    'en' => 'English description',
    'fr' => 'Description française',
], 'en');
```

### Number Values

```php
// Numeric values are automatically typed
$service->setProductValue($product, 'weight', 1.5);
// Stored as: numeric_value = 1.5, value = 1.5
```

### Boolean Values

```php
$service->setProductValue($product, 'featured', true);
// Stored as: value = true
```

### Date Values

```php
$service->setProductValue($product, 'release_date', '2024-01-01');
// Stored as: value = '2024-01-01'
```

### JSON Values

```php
$service->setProductValue($product, 'custom_data', [
    'key1' => 'value1',
    'key2' => 'value2',
]);
// Stored as: value = JSON
```

## Per-Locale Values

### Set Localized Value

```php
// Set value for specific locale
$service->setProductValue($product, 'description', 'English description', 'en');
$service->setProductValue($product, 'description', 'Description française', 'fr');
$service->setProductValue($product, 'description', 'Descripción en español', 'es');
```

### Get Localized Value

```php
// Get value for current locale
$value = $service->getValue($product, 'description', locale: 'en');
// Returns: 'English description'

$value = $service->getValue($product, 'description', locale: 'fr');
// Returns: 'Description française'
```

### Localizable Attributes

Attributes marked as `localizable` automatically store values per locale:

```php
$attribute = Attribute::where('handle', 'description')->first();
$attribute->localizable = true;
$attribute->save();

// Setting value automatically stores per locale
$service->setProductValue($product, $attribute, 'English description', 'en');
// Stored as: value = ['en' => 'English description']
```

## Per-Channel Values

### Set Channel-Specific Value

```php
// Set value for specific channel
$service->setChannelValue($product, $onlineChannel, 'description', 'Online description');
$service->setChannelValue($product, $retailChannel, 'description', 'Retail description');
```

### Get Channel-Specific Value

```php
// Get value with channel fallback
$value = $service->getValue(
    product: $product,
    attribute: 'description',
    channel: $onlineChannel
);
// Returns channel value if exists, otherwise product value
```

## Variant Overrides

### Set Variant Override

```php
// Variant overrides product value
$service->setVariantValue($variant, 'color', 'red');

// When getting value for variant:
$value = $service->getValue($product, 'color', variant: $variant);
// Returns: 'red' (variant value), not product value
```

### Check if Variant Overrides

```php
$variantValue = VariantAttributeValue::where('product_variant_id', $variant->id)
    ->where('attribute_id', $attribute->id)
    ->first();

if ($variantValue && $variantValue->is_override) {
    // Variant overrides product value
}
```

## Fallback Logic

### Automatic Fallback

The service automatically falls back through priority order:

```php
$value = $service->getValue(
    product: $product,
    attribute: 'description',
    variant: $variant,
    channel: $channel,
    locale: 'en'
);

// Fallback order:
// 1. Variant value (if variant provided and exists)
// 2. Channel value (if channel provided and exists)
// 3. Product value (if exists)
// 4. Default value (from attribute definition)
```

### Example Fallback Scenarios

```php
// Scenario 1: Variant has value
$variantValue = $service->setVariantValue($variant, 'color', 'red');
$value = $service->getValue($product, 'color', variant: $variant);
// Returns: 'red' (variant value)

// Scenario 2: Variant doesn't have value, use product value
$productValue = $service->setProductValue($product, 'color', 'blue');
$value = $service->getValue($product, 'color', variant: $variant);
// Returns: 'blue' (product value)

// Scenario 3: Neither variant nor product has value, use default
$attribute->default_value = ['value' => 'black'];
$value = $service->getValue($product, 'color', variant: $variant);
// Returns: 'black' (default value)
```

## Change History

### Automatic History Tracking

All value changes are automatically tracked:

```php
$service->setProductValue($product, 'description', 'New description');
// Automatically creates history record
```

### Get Change History

```php
// Get history for an attribute value
$history = $service->getHistory($productValue, 'description');

// Get history for specific locale
$history = $service->getHistory($productValue, 'description', locale: 'en');
```

### History Record Structure

```php
AttributeValueHistory::create([
    'valueable_type' => ProductAttributeValue::class,
    'valueable_id' => $value->id,
    'attribute_id' => $attribute->id,
    'value_before' => 'Old description',
    'value_after' => 'New description',
    'numeric_value_before' => null,
    'numeric_value_after' => null,
    'text_value_before' => 'Old description',
    'text_value_after' => 'New description',
    'change_type' => 'updated', // created, updated, deleted
    'locale' => 'en',
    'changed_by' => $userId,
]);
```

### Query History

```php
use App\Models\AttributeValueHistory;

// Get all changes for an attribute
$history = AttributeValueHistory::where('attribute_id', $attribute->id)
    ->orderByDesc('created_at')
    ->get();

// Get changes by type
$updates = AttributeValueHistory::changeType('updated')->get();
$creates = AttributeValueHistory::changeType('created')->get();

// Get changes by locale
$enHistory = AttributeValueHistory::locale('en')->get();

// Get changes by user
$userHistory = AttributeValueHistory::where('changed_by', $userId)->get();
```

## Examples

### Complete Example: Product with Variants

```php
use App\Services\AttributeValueService;

$service = app(AttributeValueService::class);

// 1. Set product-level values
$service->setProductValue($product, 'name', 'T-Shirt', 'en');
$service->setProductValue($product, 'name', 'Tee-Shirt', 'fr');
$service->setProductValue($product, 'description', 'A comfortable t-shirt', 'en');
$service->setProductValue($product, 'material', 'cotton');

// 2. Set variant-specific values (overrides)
$redVariant = $product->variants->where('sku', 'TSHIRT-RED')->first();
$service->setVariantValue($redVariant, 'color', 'red');
$service->setVariantValue($redVariant, 'name', 'Red T-Shirt', 'en'); // Override name

$blueVariant = $product->variants->where('sku', 'TSHIRT-BLUE')->first();
$service->setVariantValue($blueVariant, 'color', 'blue');

// 3. Get values with fallback
$redColor = $service->getValue($product, 'color', variant: $redVariant);
// Returns: 'red' (variant value)

$blueColor = $service->getValue($product, 'color', variant: $blueVariant);
// Returns: 'blue' (variant value)

$material = $service->getValue($product, 'material', variant: $redVariant);
// Returns: 'cotton' (product value, no variant override)

$redName = $service->getValue($product, 'name', variant: $redVariant, locale: 'en');
// Returns: 'Red T-Shirt' (variant override)

$blueName = $service->getValue($product, 'name', variant: $blueVariant, locale: 'en');
// Returns: 'T-Shirt' (product value, no variant override)
```

### Channel-Specific Values

```php
// Set channel-specific descriptions
$service->setChannelValue($product, $onlineChannel, 'description', 'Buy online now!', 'en');
$service->setChannelValue($product, $retailChannel, 'description', 'Available in stores', 'en');

// Get channel-specific value
$onlineDesc = $service->getValue($product, 'description', channel: $onlineChannel, locale: 'en');
// Returns: 'Buy online now!' (channel value)

$retailDesc = $service->getValue($product, 'description', channel: $retailChannel, locale: 'en');
// Returns: 'Available in stores' (channel value)

// Fallback to product value if channel value doesn't exist
$mobileDesc = $service->getValue($product, 'description', channel: $mobileChannel, locale: 'en');
// Returns: 'A comfortable t-shirt' (product value)
```

### Localized Values

```php
// Set values for multiple locales
$service->setProductValue($product, 'description', 'English description', 'en');
$service->setProductValue($product, 'description', 'Description française', 'fr');
$service->setProductValue($product, 'description', 'Descripción en español', 'es');

// Get value for specific locale
$enDesc = $service->getValue($product, 'description', locale: 'en');
// Returns: 'English description'

$frDesc = $service->getValue($product, 'description', locale: 'fr');
// Returns: 'Description française'

// Fallback to default locale if not available
$deDesc = $service->getValue($product, 'description', locale: 'de');
// Returns: 'English description' (fallback to first available)
```

## Best Practices

1. **Use typed storage** - Let the service handle type conversion
2. **Set locale explicitly** - Always specify locale for localized attributes
3. **Use variant overrides sparingly** - Only override when necessary
4. **Leverage fallback logic** - Don't duplicate values unnecessarily
5. **Track changes** - History is automatically tracked
6. **Use channel values** - For channel-specific content
7. **Set defaults** - Define default values in attribute definitions
8. **Validate values** - Use attribute validation before setting

## Integration

### With Product Model

```php
use App\Models\Product;
use App\Services\AttributeValueService;

$product = Product::find(1);
$service = app(AttributeValueService::class);

// Set values
$service->setProductValue($product, 'name', 'Product Name');
$service->setProductValue($product, 'description', 'Product Description', 'en');

// Get values
$name = $service->getValue($product, 'name');
$description = $service->getValue($product, 'description', locale: 'en');
```

### With Variant Model

```php
use App\Models\ProductVariant;

$variant = ProductVariant::find(1);

// Set variant override
$service->setVariantValue($variant, 'color', 'red');

// Get value (with fallback)
$color = $service->getValue($variant->product, 'color', variant: $variant);
```

### With Channel Model

```php
use Lunar\Models\Channel;

$channel = Channel::find(1);

// Set channel-specific value
$service->setChannelValue($product, $channel, 'description', 'Channel description');

// Get value (with fallback)
$description = $service->getValue($product, 'description', channel: $channel);
```


