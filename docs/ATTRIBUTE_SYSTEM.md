# Attribute System

Flexible, typed, and scalable attribute definition system for products and variants.

## Overview

The Attribute System provides:

- ✅ **First-class entities** - Attributes are standalone entities
- ✅ **Code (unique)** - Unique identifier for attributes
- ✅ **Type system** - text, select, number, boolean, color, file, date, JSON
- ✅ **Scope** - product / variant / both
- ✅ **Localizable** - Support for multiple languages
- ✅ **Channel-specific** - Different values per channel
- ✅ **Required / optional** - Validation support
- ✅ **Default value** - Pre-filled values
- ✅ **Validation rules** - Custom validation logic
- ✅ **UI hint** - dropdown, swatch, slider, etc.

## Attribute Properties

### Code (Unique Identifier)

Every attribute has a unique `code` identifier:

```php
use App\Models\Attribute;

$attribute = Attribute::where('code', 'COLOR')->first();
```

### Type System

Supported attribute types:

- `text` - Single-line text
- `select` - Single selection dropdown
- `multiselect` - Multiple selection dropdown
- `number` - Numeric value
- `boolean` - True/false
- `color` - Color picker/swatch
- `file` - File upload
- `date` - Date picker
- `datetime` - Date and time picker
- `json` - JSON data
- `textarea` - Multi-line text
- `richtext` - Rich text editor

### Scope

Attributes can be scoped to:

- `product` - Product-level only
- `variant` - Variant-level only
- `both` - Both product and variant level

### Localizable

Attributes can be marked as localizable to support multiple languages:

```php
$attribute->localizable = true;
```

### Channel-Specific

Attributes can have different values per channel:

```php
$attribute->channel_specific = true;
```

### Required / Optional

Attributes can be marked as required:

```php
$attribute->required = true;
```

### Default Value

Attributes can have default values:

```php
$attribute->default_value = ['value' => 'default'];
```

### Validation Rules

Custom validation rules can be defined:

```php
$attribute->validation_rules = [
    'min' => 0,
    'max' => 100,
    'min_length' => 5,
    'max_length' => 255,
    'pattern' => '/^[A-Z0-9]+$/',
];
```

### UI Hint

UI hints control how attributes are displayed:

- `dropdown` - Dropdown select
- `swatch` - Color swatch picker
- `slider` - Range slider
- `text` - Text input
- `textarea` - Textarea
- `checkbox` - Checkbox
- `radio` - Radio buttons
- `color_picker` - Color picker
- `file_upload` - File upload
- `date_picker` - Date picker
- `number_input` - Number input
- `json_editor` - JSON editor

## Usage

### Create Attribute Definition

```php
use App\Services\AttributeDefinitionService;

$service = app(AttributeDefinitionService::class);

$attribute = $service->createAttributeDefinition([
    'name' => 'Color',
    'handle' => 'color',
    'code' => 'COLOR', // Optional, auto-generated if not provided
    'type' => 'select',
    'scope' => 'variant',
    'localizable' => false,
    'channel_specific' => false,
    'required' => true,
    'default_value' => ['value' => 'red'],
    'ui_hint' => 'swatch',
    'validation_rules' => [
        'required' => true,
    ],
    'searchable' => true,
    'filterable' => true,
]);
```

### Update Attribute Definition

```php
$service->updateAttributeDefinition($attribute, [
    'required' => false,
    'default_value' => ['value' => 'blue'],
]);
```

### Get Attributes by Scope

```php
// Get product-level attributes
$productAttributes = $service->getAttributesByScope('product');

// Get variant-level attributes
$variantAttributes = $service->getAttributesByScope('variant');

// Get attributes available for both
$bothAttributes = $service->getAttributesByScope('both');
```

### Get Localizable Attributes

```php
$localizableAttributes = $service->getLocalizableAttributes();
```

### Get Channel-Specific Attributes

```php
$channelSpecificAttributes = $service->getChannelSpecificAttributes();
```

### Get Required Attributes

```php
$requiredAttributes = $service->getRequiredAttributes('variant');
```

### Validate Attribute Value

```php
$validation = $service->validateAttributeValue($attribute, $value);

if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        echo $error . "\n";
    }
}
```

### Get Default Value

```php
$defaultValue = $service->getDefaultValue($attribute);
```

## Attribute Model Methods

### Check Scope

```php
// Check if attribute is for products
$attribute->isProductScope(); // true/false

// Check if attribute is for variants
$attribute->isVariantScope(); // true/false
```

### Check Properties

```php
// Check if localizable
$attribute->isLocalizable(); // true/false

// Check if channel-specific
$attribute->isChannelSpecific(); // true/false

// Check if required
$attribute->isRequired(); // true/false
```

### Get Default Value

```php
$defaultValue = $attribute->getDefaultValue();
```

## Scopes

### By Scope

```php
// Get product-level attributes
Attribute::byScope('product')->get();

// Get variant-level attributes
Attribute::byScope('variant')->get();
```

### Localizable

```php
Attribute::localizable()->get();
```

### Channel-Specific

```php
Attribute::channelSpecific()->get();
```

### Required

```php
Attribute::required()->get();
```

### By UI Hint

```php
Attribute::byUIHint('swatch')->get();
```

## Attribute Types

### Text

```php
$attribute = Attribute::create([
    'name' => 'Product Name',
    'handle' => 'product_name',
    'type' => 'text',
    'scope' => 'product',
    'required' => true,
    'ui_hint' => 'text',
    'validation_rules' => [
        'min_length' => 3,
        'max_length' => 255,
    ],
]);
```

### Select

```php
$attribute = Attribute::create([
    'name' => 'Size',
    'handle' => 'size',
    'type' => 'select',
    'scope' => 'variant',
    'required' => true,
    'ui_hint' => 'dropdown',
    'configuration' => [
        'options' => [
            ['value' => 'xs', 'label' => 'Extra Small'],
            ['value' => 's', 'label' => 'Small'],
            ['value' => 'm', 'label' => 'Medium'],
            ['value' => 'l', 'label' => 'Large'],
            ['value' => 'xl', 'label' => 'Extra Large'],
        ],
    ],
]);
```

### Color (Swatch)

```php
$attribute = Attribute::create([
    'name' => 'Color',
    'handle' => 'color',
    'type' => 'color',
    'scope' => 'variant',
    'required' => true,
    'ui_hint' => 'swatch',
    'configuration' => [
        'options' => [
            ['value' => '#FF0000', 'label' => 'Red'],
            ['value' => '#00FF00', 'label' => 'Green'],
            ['value' => '#0000FF', 'label' => 'Blue'],
        ],
    ],
]);
```

### Number (Slider)

```php
$attribute = Attribute::create([
    'name' => 'Weight',
    'handle' => 'weight',
    'type' => 'number',
    'scope' => 'variant',
    'required' => false,
    'ui_hint' => 'slider',
    'validation_rules' => [
        'min' => 0,
        'max' => 1000,
    ],
    'unit' => 'kg',
]);
```

### Boolean

```php
$attribute = Attribute::create([
    'name' => 'Featured',
    'handle' => 'featured',
    'type' => 'boolean',
    'scope' => 'product',
    'required' => false,
    'ui_hint' => 'checkbox',
    'default_value' => ['value' => false],
]);
```

### Date

```php
$attribute = Attribute::create([
    'name' => 'Release Date',
    'handle' => 'release_date',
    'type' => 'date',
    'scope' => 'product',
    'required' => false,
    'ui_hint' => 'date_picker',
]);
```

### File

```php
$attribute = Attribute::create([
    'name' => 'Manual PDF',
    'handle' => 'manual_pdf',
    'type' => 'file',
    'scope' => 'product',
    'required' => false,
    'ui_hint' => 'file_upload',
    'validation_rules' => [
        'mimes' => 'pdf',
        'max_size' => 10240, // 10MB
    ],
]);
```

### JSON

```php
$attribute = Attribute::create([
    'name' => 'Custom Data',
    'handle' => 'custom_data',
    'type' => 'json',
    'scope' => 'product',
    'required' => false,
    'ui_hint' => 'json_editor',
    'default_value' => ['value' => []],
]);
```

## Validation

### Type Validation

The system automatically validates values based on attribute type:

```php
$validation = $service->validateAttributeValue($attribute, $value);

// Returns:
// [
//     'valid' => bool,
//     'errors' => array,
// ]
```

### Custom Validation Rules

```php
$attribute->validation_rules = [
    // Number validation
    'min' => 0,
    'max' => 100,
    
    // Text validation
    'min_length' => 5,
    'max_length' => 255,
    'pattern' => '/^[A-Z0-9]+$/',
    
    // File validation
    'mimes' => 'pdf,doc,docx',
    'max_size' => 10240, // KB
];
```

## UI Hints

### Dropdown

```php
$attribute->ui_hint = 'dropdown';
// Renders as dropdown select
```

### Swatch

```php
$attribute->ui_hint = 'swatch';
// Renders as color swatch picker
```

### Slider

```php
$attribute->ui_hint = 'slider';
// Renders as range slider
```

### Color Picker

```php
$attribute->ui_hint = 'color_picker';
// Renders as color picker
```

## Localization

### Create Localizable Attribute

```php
$attribute = Attribute::create([
    'name' => 'Description',
    'handle' => 'description',
    'type' => 'textarea',
    'scope' => 'product',
    'localizable' => true,
    'required' => true,
]);
```

### Get Localized Value

```php
// Attribute values are automatically localized based on current locale
$value = $product->attribute_data['description'];
```

## Channel-Specific Attributes

### Create Channel-Specific Attribute

```php
$attribute = Attribute::create([
    'name' => 'Channel Description',
    'handle' => 'channel_description',
    'type' => 'textarea',
    'scope' => 'product',
    'channel_specific' => true,
]);
```

### Get Channel-Specific Value

```php
// Values are stored per channel
$value = $product->getChannelAttributeValue('channel_description', $channelId);
```

## Best Practices

1. **Use unique codes** - Always provide unique codes for attributes
2. **Set appropriate scope** - Use 'product', 'variant', or 'both' appropriately
3. **Mark localizable** - Mark attributes that need translation
4. **Set validation rules** - Define validation rules for data integrity
5. **Choose UI hints** - Select appropriate UI hints for better UX
6. **Set defaults** - Provide default values when appropriate
7. **Use required flag** - Mark required attributes appropriately
8. **Document attributes** - Document attribute purpose and usage

## Examples

### Complete Attribute Definition

```php
use App\Services\AttributeDefinitionService;

$service = app(AttributeDefinitionService::class);

// Color attribute for variants
$colorAttribute = $service->createAttributeDefinition([
    'name' => 'Color',
    'handle' => 'color',
    'code' => 'VAR_COLOR',
    'type' => 'select',
    'scope' => 'variant',
    'localizable' => false,
    'channel_specific' => false,
    'required' => true,
    'default_value' => ['value' => 'black'],
    'ui_hint' => 'swatch',
    'validation_rules' => [
        'required' => true,
    ],
    'searchable' => true,
    'filterable' => true,
    'configuration' => [
        'options' => [
            ['value' => 'black', 'label' => 'Black', 'hex' => '#000000'],
            ['value' => 'white', 'label' => 'White', 'hex' => '#FFFFFF'],
            ['value' => 'red', 'label' => 'Red', 'hex' => '#FF0000'],
        ],
    ],
]);

// Description attribute for products
$descriptionAttribute = $service->createAttributeDefinition([
    'name' => 'Description',
    'handle' => 'description',
    'code' => 'PROD_DESC',
    'type' => 'richtext',
    'scope' => 'product',
    'localizable' => true,
    'channel_specific' => false,
    'required' => true,
    'ui_hint' => 'textarea',
    'validation_rules' => [
        'min_length' => 50,
        'max_length' => 5000,
    ],
    'searchable' => true,
]);
```

## Integration

### With Product Model

```php
use App\Models\Product;

$product = Product::find(1);

// Get attribute value
$color = $product->attribute_data['color'];

// Set attribute value
$product->attribute_data = array_merge($product->attribute_data, [
    'color' => 'red',
]);
$product->save();
```

### With Variant Model

```php
use App\Models\ProductVariant;

$variant = ProductVariant::find(1);

// Get attribute value
$size = $variant->attribute_data['size'];

// Set attribute value
$variant->attribute_data = array_merge($variant->attribute_data, [
    'size' => 'large',
]);
$variant->save();
```


