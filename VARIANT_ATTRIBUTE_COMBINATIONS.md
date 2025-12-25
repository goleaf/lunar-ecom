# Variant Attribute Combinations System

Complete architecture for managing variant attribute combinations with advanced features.

## Overview

This system provides:

1. **Attribute Logic** - Attribute → Value pairs, unlimited attributes, variant-defining vs informational
2. **Attribute Value Normalization** - Normalize different spellings/values
3. **Attribute Inheritance** - Inherit attributes from product
4. **Enforced Uniqueness** - Ensure unique combinations
5. **Partial Variants** - Support for drafts with missing attributes
6. **Auto-generation** - Generate variants from attribute matrix
7. **Manual Creation** - Create variants manually
8. **Templates/Presets** - Reusable variant templates
9. **Invalid Combinations** - Disable invalid combinations
10. **Dependency Rules** - Attribute dependency rules (e.g., "XL only exists in Black")

## Core Concepts

### Variant-Defining Attributes

Attributes that are **required** to create a complete variant. These define the variant identity.

**Example**:
- Color (Red, Blue, Green)
- Size (S, M, L, XL)

**Configuration**:
```php
$product->custom_meta['variant_defining_attributes'] = [1, 2]; // Option IDs
```

### Informational Attributes

Attributes that provide **additional information** but don't define the variant. Used for filtering/display.

**Example**:
- Material (Cotton, Polyester)
- Pattern (Solid, Striped)

### Attribute Combination

A unique set of attribute → value pairs that defines a variant.

**Format**: `{option_id: value_id, option_id: value_id}`

**Example**:
```php
[
    1 => 10, // Color: Red
    2 => 25, // Size: XL
]
```

## Database Structure

### Variant Attribute Combinations Table

```sql
id BIGINT PRIMARY KEY
product_id BIGINT (FK to products)
variant_id BIGINT (FK to product_variants, nullable)
combination JSON (option_id => value_id pairs)
combination_hash VARCHAR(64) UNIQUE (for quick lookup)
defining_attributes JSON (array of option IDs)
informational_attributes JSON (array of option IDs)
status ENUM('draft', 'active', 'disabled')
is_partial BOOLEAN (missing some defining attributes)
template_id BIGINT (FK to variant_templates)
created_at TIMESTAMP
updated_at TIMESTAMP
```

### Variant Attribute Dependencies Table

```sql
id BIGINT PRIMARY KEY
product_id BIGINT (FK to products, nullable for global rules)
type ENUM('requires', 'excludes', 'allows_only', 'requires_one_of')
source_option_id BIGINT (FK to product_options)
source_value_id BIGINT (FK to product_option_values, nullable)
target_option_id BIGINT (FK to product_options)
target_value_ids JSON (array of value IDs)
config JSON (rule configuration)
priority INT (for multiple rules)
is_active BOOLEAN
created_at TIMESTAMP
updated_at TIMESTAMP
```

### Variant Templates Table

```sql
id BIGINT PRIMARY KEY
name VARCHAR(255)
description TEXT
type ENUM('preset', 'template', 'pattern')
product_type_id BIGINT (FK to product_types, nullable)
default_combination JSON
default_fields JSON
attribute_config JSON
usage_count INT
is_active BOOLEAN
created_at TIMESTAMP
updated_at TIMESTAMP
```

### Variant Attribute Normalizations Table

```sql
id BIGINT PRIMARY KEY
option_id BIGINT (FK to product_options)
source_value VARCHAR(255) (what user enters)
normalized_value_id BIGINT (FK to product_option_values)
type ENUM('synonym', 'alias', 'normalize', 'map')
case_sensitive BOOLEAN
priority INT
is_active BOOLEAN
created_at TIMESTAMP
updated_at TIMESTAMP
UNIQUE(option_id, source_value, normalized_value_id)
```

## Services

### VariantAttributeCombinationService

Manages variant attribute combinations.

**Location**: `app/Services/VariantAttributeCombinationService.php`

**Key Methods**:

```php
use App\Services\VariantAttributeCombinationService;

$service = app(VariantAttributeCombinationService::class);

// Create variant from combination
$variant = $service->createVariantFromCombination($product, [
    1 => 10, // Color: Red
    2 => 25, // Size: XL
], [
    'status' => 'active',
    'variant_data' => ['stock' => 100],
]);

// Normalize combination
$normalized = $service->normalizeCombination([
    1 => 'red', // String value
    2 => 25,    // Value ID
]);

// Validate combination
$validation = $service->validateCombination($product, $combination);
// Returns: ['valid' => bool, 'message' => string]

// Check combination validity with allowed values
$result = $service->checkCombinationValidity($product, $combination);
// Returns: ['valid' => bool, 'message' => string, 'allowed_values' => array]

// Get allowed values for options
$allowedValues = $service->getAllowedValues($product, $currentCombination);
// Returns: [option_id => [value_id, value_id, ...]]

// Get defining attributes
$defining = $service->getDefiningAttributes($product);
// Returns: [option_id, option_id, ...]

// Check if combination is partial
$isPartial = $service->isPartialCombination($product, $combination, $defining);

// Check uniqueness
$isUnique = $service->isUniqueCombination($product, $combination, $excludeVariantId);

// Get variant by combination
$variant = $service->getVariantByCombination($product, $combination);

// Get all combinations
$combinations = $service->getCombinations($product, $includePartial);

// Get invalid combinations
$invalid = $service->getInvalidCombinations($product);
```

### VariantMatrixGeneratorService

Auto-generates variants from attribute matrix.

**Location**: `app/Services/VariantMatrixGeneratorService.php`

**Key Methods**:

```php
use App\Services\VariantMatrixGeneratorService;

$service = app(VariantMatrixGeneratorService::class);

// Generate from matrix
$variants = $service->generateFromMatrix($product, [
    'defining_attributes' => [1, 2], // Color, Size
    'status' => 'active',
    'defaults' => ['stock' => 0],
]);

// Generate from template
$variants = $service->generateFromTemplate($product, $template, [
    'combination' => [],
    'fields' => ['stock' => 100],
]);

// Generate with dependencies
$variants = $service->generateWithDependencies($product, [
    'status' => 'active',
]);
```

### VariantDependencyService

Manages attribute dependency rules.

**Location**: `app/Services/VariantDependencyService.php`

**Key Methods**:

```php
use App\Services\VariantDependencyService;

$service = app(VariantDependencyService::class);

// Create "requires" dependency
// Example: "If Color = Red, then Size is required"
$dependency = $service->createRequiresDependency(
    $sourceOptionId,    // Color option ID
    $sourceValueId,      // Red value ID
    $targetOptionId,    // Size option ID
    $requiredValueIds,   // [S, M, L, XL]
    $productId           // null for global rule
);

// Create "excludes" dependency
// Example: "If Size = XL, then Color cannot be Black"
$dependency = $service->createExcludesDependency(
    $sourceOptionId,    // Size option ID
    $sourceValueId,     // XL value ID
    $targetOptionId,    // Color option ID
    $excludedValueIds,  // [Black value ID]
    $productId
);

// Create "allows_only" dependency
// Example: "If Size = XL, then only Black and White colors are allowed"
$dependency = $service->createAllowsOnlyDependency(
    $sourceOptionId,    // Size option ID
    $sourceValueId,     // XL value ID
    $targetOptionId,    // Color option ID
    $allowedValueIds,   // [Black, White value IDs]
    $productId
);

// Get dependencies for product
$dependencies = $service->getDependencies($product);

// Get disabled combinations
$disabled = $service->getDisabledCombinations($product);

// Validate against dependencies
$result = $service->validateAgainstDependencies($product, $combination);
// Returns: ['valid' => bool, 'errors' => array]
```

## Usage Examples

### Create Variant Manually

```php
use App\Services\VariantAttributeCombinationService;

$service = app(VariantAttributeCombinationService::class);

// Create variant with specific combination
$variant = $service->createVariantFromCombination($product, [
    1 => 10, // Color: Red
    2 => 25, // Size: XL
], [
    'status' => 'active',
    'variant_data' => [
        'stock' => 100,
        'price_override' => 5000,
    ],
]);
```

### Auto-Generate Variants

```php
use App\Services\VariantMatrixGeneratorService;

$service = app(VariantMatrixGeneratorService::class);

// Generate all combinations
$variants = $service->generateFromMatrix($product, [
    'defining_attributes' => [1, 2], // Color and Size
    'status' => 'active',
    'defaults' => [
        'stock' => 0,
        'enabled' => true,
    ],
]);

// Generate with dependency filtering
$variants = $service->generateWithDependencies($product, [
    'status' => 'active',
]);
```

### Create Dependency Rules

```php
use App\Services\VariantDependencyService;

$service = app(VariantDependencyService::class);

// Example: "XL only exists in Black"
$sizeOption = ProductOption::where('handle', 'size')->first();
$colorOption = ProductOption::where('handle', 'color')->first();
$xlValue = ProductOptionValue::where('name', 'XL')->first();
$blackValue = ProductOptionValue::where('name', 'Black')->first();

$service->createAllowsOnlyDependency(
    $sizeOption->id,
    $xlValue->id,
    $colorOption->id,
    [$blackValue->id],
    $product->id
);
```

### Validate Combination

```php
$service = app(VariantAttributeCombinationService::class);

// Check if combination is valid
$result = $service->checkCombinationValidity($product, [
    1 => 10, // Color: Red
    2 => 25, // Size: XL
]);

if (!$result['valid']) {
    echo "Invalid: " . $result['message'];
    print_r($result['allowed_values']);
}
```

### Get Allowed Values

```php
// Get allowed values based on current selection
$allowedValues = $service->getAllowedValues($product, [
    1 => 10, // Color: Red selected
]);

// Returns: [2 => [25, 26, 27]] // Allowed sizes for Red
```

### Create Variant Template

```php
use App\Models\VariantTemplate;

$template = VariantTemplate::create([
    'name' => 'Standard Clothing Variants',
    'type' => 'template',
    'default_combination' => [
        1 => 10, // Default color
    ],
    'default_fields' => [
        'stock' => 0,
        'enabled' => true,
        'purchasable' => 'in_stock',
    ],
    'attribute_config' => [
        'defining' => [1, 2], // Color, Size
        'informational' => [3], // Material
    ],
]);

// Apply template to product
$service = app(VariantMatrixGeneratorService::class);
$variants = $service->generateFromTemplate($product, $template);
```

### Normalize Attribute Values

```php
use App\Models\VariantAttributeNormalization;

// Create normalization rule
VariantAttributeNormalization::create([
    'option_id' => $colorOption->id,
    'source_value' => 'red',
    'normalized_value_id' => $redValue->id,
    'type' => 'normalize',
    'case_sensitive' => false,
]);

// Normalize value
$normalizedId = VariantAttributeNormalization::normalize($colorOption->id, 'RED');
// Returns: $redValue->id
```

### Partial Variants (Drafts)

```php
// Create partial variant (missing some defining attributes)
$variant = $service->createVariantFromCombination($product, [
    1 => 10, // Color: Red
    // Size not selected yet
], [
    'status' => 'draft',
    'allow_partial' => true,
]);

// Complete partial variant later
$variant->variantOptions()->attach([$sizeValueId]);
$variant->attributeCombination->update(['is_partial' => false, 'status' => 'active']);
```

## Dependency Rule Types

### Requires

**Example**: "If Color = Red, then Size is required"

```php
$service->createRequiresDependency(
    $colorOptionId,
    $redValueId,
    $sizeOptionId,
    [$sValueId, $mValueId, $lValueId, $xlValueId],
    $productId
);
```

### Excludes

**Example**: "If Size = XL, then Color cannot be Black"

```php
$service->createExcludesDependency(
    $sizeOptionId,
    $xlValueId,
    $colorOptionId,
    [$blackValueId],
    $productId
);
```

### Allows Only

**Example**: "If Size = XL, then only Black and White colors are allowed"

```php
$service->createAllowsOnlyDependency(
    $sizeOptionId,
    $xlValueId,
    $colorOptionId,
    [$blackValueId, $whiteValueId],
    $productId
);
```

### Requires One Of

**Example**: "If Color = Red, then at least one of Pattern options must be selected"

```php
$service->createDependency([
    'type' => 'requires_one_of',
    'source_option_id' => $colorOptionId,
    'source_value_id' => $redValueId,
    'target_option_id' => $patternOptionId,
    'target_value_ids' => [$solidValueId, $stripedValueId],
]);
```

## API Endpoints

### Generate Variants

```http
POST /api/admin/products/{id}/variants/generate-matrix
Content-Type: application/json

{
    "defining_attributes": [1, 2],
    "status": "active",
    "defaults": {
        "stock": 0,
        "enabled": true
    }
}
```

### Create Variant Manually

```http
POST /api/admin/products/{id}/variants/create-manual
Content-Type: application/json

{
    "combination": {
        "1": 10,
        "2": 25
    },
    "variant_data": {
        "stock": 100,
        "price_override": 5000
    }
}
```

### Validate Combination

```http
POST /api/admin/products/{id}/variants/validate-combination
Content-Type: application/json

{
    "combination": {
        "1": 10,
        "2": 25
    }
}
```

### Get Allowed Values

```http
GET /api/admin/products/{id}/variants/allowed-values?combination[1]=10
```

### Create Dependency

```http
POST /api/admin/products/{id}/dependencies
Content-Type: application/json

{
    "type": "allows_only",
    "source_option_id": 2,
    "source_value_id": 25,
    "target_option_id": 1,
    "target_value_ids": [10, 11],
    "config": {
        "message": "XL only available in Red and Blue"
    }
}
```

### Apply Template

```http
POST /api/admin/products/{id}/variants/apply-template/{templateId}
Content-Type: application/json

{
    "combination": {},
    "fields": {
        "stock": 100
    }
}
```

## Best Practices

1. **Defining Attributes**: Set defining attributes at product level
2. **Dependencies**: Create dependencies before generating variants
3. **Normalization**: Set up normalization rules for common variations
4. **Templates**: Create templates for common variant patterns
5. **Validation**: Always validate combinations before creating variants
6. **Partial Variants**: Use partial variants for drafts, complete before publishing
7. **Uniqueness**: Always check uniqueness before creating variants
8. **Dependency Priority**: Use priority to control rule application order

## Notes

- Each variant = one unique attribute combination
- Combinations are hashed for quick lookup
- Partial variants allow drafts with missing attributes
- Dependencies can be product-specific or global
- Normalization handles spelling/casing variations
- Templates speed up variant creation
- All combinations are validated against dependencies

