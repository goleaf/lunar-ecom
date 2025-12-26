# Attribute Sets

Complete attribute sets system with reusable groups, inheritance, conditional visibility, and ordering.

## Overview

The Attribute Sets System provides:

- ✅ **Attribute sets per product type** - Different sets for different product types
- ✅ **Reusable attribute groups** - Share groups across multiple sets
- ✅ **Inheritance between sets** - Child sets inherit from parent sets
- ✅ **Conditional visibility** - Show/hide groups and attributes based on conditions
- ✅ **Attribute ordering** - Control display order of groups and attributes

## Architecture

### Attribute Sets
- Assigned to product types
- Contains multiple attribute groups
- Supports inheritance from parent sets

### Attribute Groups
- Reusable groups of attributes
- Can be shared across multiple sets
- Contains multiple attributes

### Attributes
- Individual attribute definitions
- Belong to groups
- Support conditional visibility

## Usage

### Create Attribute Set

```php
use App\Services\AttributeSetService;

$service = app(AttributeSetService::class);

$set = $service->createAttributeSet([
    'name' => 'Clothing Product Set',
    'handle' => 'clothing-product-set',
    'code' => 'CLOTHING_SET',
    'product_type_id' => $productType->id,
    'is_default' => true,
    'group_ids' => [$group1->id, $group2->id],
    'group_positions' => [0, 1],
]);
```

### Create Reusable Attribute Group

```php
$group = $service->createAttributeGroup([
    'name' => 'Basic Information',
    'handle' => 'basic-information',
    'code' => 'BASIC_INFO',
    'is_reusable' => true,
    'attribute_ids' => [$attr1->id, $attr2->id],
    'attribute_positions' => [0, 1],
]);
```

### Attach Groups to Set

```php
$service->attachGroups($set, [$group1->id, $group2->id], [0, 1]);
```

### Attach Attributes to Group

```php
$service->attachAttributes($group, [$attr1->id, $attr2->id], [0, 1]);
```

## Inheritance

### Create Child Set

```php
$childSet = $service->createAttributeSet([
    'name' => 'Premium Clothing Set',
    'handle' => 'premium-clothing-set',
    'parent_set_id' => $parentSet->id, // Inherit from parent
    'product_type_id' => $productType->id,
    'group_ids' => [$additionalGroup->id], // Additional groups
]);
```

### Get All Attributes (Including Inherited)

```php
// Get all attributes including inherited ones
$allAttributes = $set->getAllAttributes();
```

## Conditional Visibility

### Set Group Visibility Conditions

```php
$service->setGroupVisibilityConditions($set, $groupId, [
    [
        'field' => 'product_type',
        'operator' => 'equals',
        'value' => 'premium',
    ],
    [
        'field' => 'channel',
        'operator' => 'contains',
        'value' => 'online',
    ],
]);
```

### Set Attribute Visibility Conditions

```php
$service->setAttributeVisibilityConditions($group, $attributeId, [
    [
        'field' => 'has_variants',
        'operator' => 'equals',
        'value' => true,
    ],
]);
```

### Check Visibility

```php
// Get visible groups for a set
$visibleGroups = $service->getVisibleGroups($set, [
    'product_type' => 'premium',
    'channel' => ['online', 'mobile'],
]);

// Get visible attributes for a group
$visibleAttributes = $service->getVisibleAttributes($group, [
    'has_variants' => true,
]);
```

### Visibility Operators

Supported operators:
- `equals` - Field equals value
- `not_equals` - Field does not equal value
- `contains` - Array contains value
- `not_contains` - Array does not contain value
- `greater_than` - Numeric comparison
- `less_than` - Numeric comparison
- `is_empty` - Field is empty
- `is_not_empty` - Field is not empty

## Attribute Ordering

### Reorder Groups

```php
// Reorder groups in a set
$service->reorderGroups($set, [$group3->id, $group1->id, $group2->id]);
```

### Reorder Attributes

```php
// Reorder attributes in a group
$service->reorderAttributes($group, [$attr3->id, $attr1->id, $attr2->id]);
```

## Product Type Integration

### Get Attribute Set for Product Type

```php
$set = $service->getAttributeSetForProductType($productType);
```

### Get All Attributes for Product Type

```php
$attributes = $service->getAttributesForProductType($productType);
```

## Examples

### Complete Example: Clothing Product Set

```php
use App\Services\AttributeSetService;
use App\Models\AttributeGroup;
use App\Models\Attribute;

$service = app(AttributeSetService::class);

// 1. Create reusable groups
$basicInfoGroup = $service->createAttributeGroup([
    'name' => 'Basic Information',
    'handle' => 'basic-information',
    'is_reusable' => true,
    'attribute_ids' => [
        Attribute::where('handle', 'name')->first()->id,
        Attribute::where('handle', 'description')->first()->id,
        Attribute::where('handle', 'sku')->first()->id,
    ],
]);

$pricingGroup = $service->createAttributeGroup([
    'name' => 'Pricing',
    'handle' => 'pricing',
    'is_reusable' => true,
    'attribute_ids' => [
        Attribute::where('handle', 'price')->first()->id,
        Attribute::where('handle', 'compare_at_price')->first()->id,
    ],
]);

$clothingGroup = $service->createAttributeGroup([
    'name' => 'Clothing Details',
    'handle' => 'clothing-details',
    'is_reusable' => false, // Specific to clothing
    'attribute_ids' => [
        Attribute::where('handle', 'size')->first()->id,
        Attribute::where('handle', 'color')->first()->id,
        Attribute::where('handle', 'material')->first()->id,
    ],
]);

// 2. Create attribute set
$clothingSet = $service->createAttributeSet([
    'name' => 'Clothing Product Set',
    'handle' => 'clothing-product-set',
    'product_type_id' => $clothingProductType->id,
    'is_default' => true,
    'group_ids' => [
        $basicInfoGroup->id,
        $pricingGroup->id,
        $clothingGroup->id,
    ],
    'group_positions' => [0, 1, 2],
]);

// 3. Set conditional visibility
// Show pricing group only for online channel
$service->setGroupVisibilityConditions($clothingSet, $pricingGroup->id, [
    [
        'field' => 'channel',
        'operator' => 'contains',
        'value' => 'online',
    ],
]);

// Show size attribute only if has_variants is true
$sizeAttribute = Attribute::where('handle', 'size')->first();
$service->setAttributeVisibilityConditions($clothingGroup, $sizeAttribute->id, [
    [
        'field' => 'has_variants',
        'operator' => 'equals',
        'value' => true,
    ],
]);
```

### Inheritance Example

```php
// Create base set
$baseSet = $service->createAttributeSet([
    'name' => 'Base Product Set',
    'handle' => 'base-product-set',
    'product_type_id' => $baseProductType->id,
    'group_ids' => [$basicInfoGroup->id, $pricingGroup->id],
]);

// Create premium set that inherits from base
$premiumSet = $service->createAttributeSet([
    'name' => 'Premium Product Set',
    'handle' => 'premium-product-set',
    'parent_set_id' => $baseSet->id, // Inherit from base
    'product_type_id' => $premiumProductType->id,
    'group_ids' => [$premiumGroup->id], // Additional groups
]);

// Get all attributes (includes inherited)
$allAttributes = $premiumSet->getAllAttributes();
// Returns: attributes from baseSet + attributes from premiumSet
```

### Reusable Groups Example

```php
// Create reusable group
$seoGroup = $service->createAttributeGroup([
    'name' => 'SEO',
    'handle' => 'seo',
    'is_reusable' => true,
    'attribute_ids' => [
        Attribute::where('handle', 'meta_title')->first()->id,
        Attribute::where('handle', 'meta_description')->first()->id,
        Attribute::where('handle', 'meta_keywords')->first()->id,
    ],
]);

// Use in multiple sets
$clothingSet->groups()->attach($seoGroup->id, ['position' => 10]);
$electronicsSet->groups()->attach($seoGroup->id, ['position' => 10]);
$booksSet->groups()->attach($seoGroup->id, ['position' => 10]);
```

## Model Methods

### AttributeSet

```php
// Get product type
$set->productType;

// Get parent set (inheritance)
$set->parentSet;

// Get child sets
$set->childSets;

// Get groups
$set->groups;

// Get all attributes (including inherited)
$set->getAllAttributes();

// Scopes
AttributeSet::active()->get();
AttributeSet::default()->get();
AttributeSet::forProductType($productTypeId)->get();
```

### AttributeGroup

```php
// Get attribute sets using this group
$group->attributeSets;

// Get attributes in this group
$group->attributes;

// Scopes
AttributeGroup::reusable()->get();
AttributeGroup::active()->get();
```

## Best Practices

1. **Use reusable groups** - Create reusable groups for common attributes (SEO, pricing, etc.)
2. **Leverage inheritance** - Use parent sets for common attributes, child sets for specific ones
3. **Set visibility conditions** - Use conditional visibility to show/hide based on context
4. **Order consistently** - Maintain consistent ordering across sets
5. **Name clearly** - Use clear, descriptive names for sets and groups
6. **Document conditions** - Document visibility conditions for maintainability
7. **Test visibility** - Test visibility conditions with different contexts
8. **Reuse groups** - Reuse groups across multiple sets when possible

## Visibility Conditions

### Example Conditions

```php
// Show group only for specific product type
[
    'field' => 'product_type',
    'operator' => 'equals',
    'value' => 'premium',
]

// Show attribute only if has variants
[
    'field' => 'has_variants',
    'operator' => 'equals',
    'value' => true,
]

// Show group only for online channel
[
    'field' => 'channel',
    'operator' => 'contains',
    'value' => 'online',
]

// Show attribute only if price > 100
[
    'field' => 'price',
    'operator' => 'greater_than',
    'value' => 100,
]

// Multiple conditions (AND logic)
[
    [
        'field' => 'product_type',
        'operator' => 'equals',
        'value' => 'premium',
    ],
    [
        'field' => 'channel',
        'operator' => 'contains',
        'value' => 'online',
    ],
]
```

## Integration

### With Product Types

```php
use Lunar\Models\ProductType;

$productType = ProductType::find(1);

// Get attribute set for product type
$set = $service->getAttributeSetForProductType($productType);

// Get all attributes
$attributes = $service->getAttributesForProductType($productType);
```

### With Products

```php
use App\Models\Product;

$product = Product::find(1);

// Get attribute set
$set = $service->getAttributeSetForProductType($product->product_type);

// Get visible groups
$visibleGroups = $service->getVisibleGroups($set, [
    'product_type' => $product->product_type->handle,
    'channel' => $currentChannel->handle,
]);
```

## Advanced Usage

### Dynamic Group Visibility

```php
// Show group based on product data
$context = [
    'product_type' => $product->product_type->handle,
    'has_variants' => $product->variants->count() > 0,
    'price' => $product->variants->first()->price->value ?? 0,
    'channel' => $currentChannel->handle,
];

$visibleGroups = $service->getVisibleGroups($set, $context);
```

### Conditional Attribute Requirements

```php
// Set attribute as required conditionally
$group->attributes()->updateExistingPivot($attributeId, [
    'is_required' => true,
    'visibility_conditions' => [
        [
            'field' => 'product_type',
            'operator' => 'equals',
            'value' => 'premium',
        ],
    ],
]);
```


