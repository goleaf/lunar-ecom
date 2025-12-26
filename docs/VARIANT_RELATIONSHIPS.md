# Variant Relationships

Complete variant relationship system for linking variants together.

## Overview

Variants aren't isolated! This system provides comprehensive relationship management:

1. **Cross-variant linking** - Same product, different attributes (e.g., different color)
2. **Replacement variants** - Replacement variants (e.g., newer model)
3. **Upgrade / downgrade variants** - Better or lower tier variants
4. **Accessory variants** - Complementary products
5. **Bundle component variants** - Components of bundles
6. **Compatible variants** - Compatible products
7. **Alternative variants** - Alternative options

## Relationship Types

### Supported Types

- **`cross_variant`** - Same product, different attributes (e.g., different color/size)
- **`replacement`** - Replacement variant (e.g., newer model, discontinued item replacement)
- **`upgrade`** - Upgrade variant (better version, higher tier)
- **`downgrade`** - Downgrade variant (lower tier, budget version)
- **`accessory`** - Accessory variant (complementary product)
- **`bundle_component`** - Component of a bundle
- **`compatible`** - Compatible variant (works with this variant)
- **`alternative`** - Alternative variant (similar option)

## Database Structure

### Variant Relationships Table

- `variant_id` - Source variant ID
- `related_variant_id` - Related variant ID
- `relationship_type` - Type of relationship
- `label` - Custom label for the relationship
- `description` - Description of the relationship
- `sort_order` - Sort order for display
- `is_active` - Active flag
- `is_bidirectional` - If true, creates reverse relationship
- `metadata` - Additional metadata (JSON)

## Usage

### VariantRelationshipService

```php
use App\Services\VariantRelationshipService;

$service = app(VariantRelationshipService::class);
```

### Create Relationships

```php
// Cross-variant linking (same product, different color)
$service->createRelationship(
    $redVariant,
    $blueVariant,
    'cross_variant',
    [
        'label' => 'Available in other colors',
        'is_bidirectional' => true, // Both variants link to each other
        'sort_order' => 1,
    ]
);

// Replacement variant
$service->createRelationship(
    $oldVariant,
    $newVariant,
    'replacement',
    [
        'label' => 'Newer model available',
        'description' => 'This variant has been replaced by a newer model',
        'metadata' => [
            'replacement_reason' => 'Product refresh',
            'discontinued_date' => '2025-01-01',
        ],
    ]
);

// Upgrade variant
$service->createRelationship(
    $basicVariant,
    $premiumVariant,
    'upgrade',
    [
        'label' => 'Upgrade to Premium',
        'description' => 'Get more features with the premium version',
        'sort_order' => 1,
    ]
);

// Downgrade variant
$service->createRelationship(
    $premiumVariant,
    $basicVariant,
    'downgrade',
    [
        'label' => 'Budget option available',
        'description' => 'Save money with the basic version',
    ]
);

// Accessory variant
$service->createRelationship(
    $phoneVariant,
    $caseVariant,
    'accessory',
    [
        'label' => 'Recommended accessories',
        'description' => 'Protect your phone with this case',
        'sort_order' => 1,
    ]
);

// Bundle component
$service->createRelationship(
    $bundleVariant,
    $componentVariant,
    'bundle_component',
    [
        'label' => 'Included in bundle',
        'metadata' => [
            'quantity' => 2,
            'required' => true,
        ],
    ]
);

// Compatible variant
$service->createRelationship(
    $phoneVariant,
    $chargerVariant,
    'compatible',
    [
        'label' => 'Compatible accessories',
        'metadata' => [
            'compatibility_notes' => 'Works with all models',
        ],
    ]
);

// Alternative variant
$service->createRelationship(
    $variantA,
    $variantB,
    'alternative',
    [
        'label' => 'Similar options',
        'description' => 'Consider this alternative',
    ]
);
```

### Get Relationships

```php
// Get all relationships
$relationships = $service->getRelationships($variant);

// Get specific relationship type
$crossVariants = $service->getRelationships($variant, 'cross_variant');

// Include inactive relationships
$allRelationships = $service->getRelationships($variant, null, true);
```

### Get Specific Relationship Types

```php
// Cross-variants (same product, different attributes)
$crossVariants = $service->getCrossVariants($variant);

// Replacements
$replacements = $service->getReplacements($variant);

// Upgrades
$upgrades = $service->getUpgrades($variant);

// Downgrades
$downgrades = $service->getDowngrades($variant);

// Accessories
$accessories = $service->getAccessories($variant);

// Bundle components
$bundleComponents = $service->getBundleComponents($variant);

// Compatible
$compatible = $service->getCompatible($variant);

// Alternatives
$alternatives = $service->getAlternatives($variant);
```

### Delete Relationships

```php
// Delete specific relationship
$service->deleteRelationship($variant, $relatedVariant, 'cross_variant');

// Delete all relationships between variants
$service->deleteRelationship($variant, $relatedVariant);
```

### Auto-Generate Cross-Variants

```php
// Automatically create cross-variant relationships for all variants of the same product
$created = $service->autoGenerateCrossVariants($variant);
// Returns number of relationships created
```

### Get All Relationships Grouped

```php
// Get all relationships grouped by type
$allRelationships = $service->getAllRelationshipsGrouped($variant);

// Returns:
// [
//     'cross_variants' => Collection,
//     'replacements' => Collection,
//     'upgrades' => Collection,
//     'downgrades' => Collection,
//     'accessories' => Collection,
//     'bundle_components' => Collection,
//     'compatible' => Collection,
//     'alternatives' => Collection,
// ]
```

## Model Methods

### ProductVariant Methods

```php
// Get cross-variants
$crossVariants = $variant->getCrossVariants();

// Get replacements
$replacements = $variant->getReplacements();

// Get upgrades
$upgrades = $variant->getUpgrades();

// Get downgrades
$downgrades = $variant->getDowngrades();

// Get accessories
$accessories = $variant->getAccessories();

// Get bundle components
$bundleComponents = $variant->getBundleComponents();

// Get compatible
$compatible = $variant->getCompatible();

// Get alternatives
$alternatives = $variant->getAlternatives();

// Get all relationships grouped
$allRelationships = $variant->getAllRelationships();

// Create relationship
$relationship = $variant->relateTo($relatedVariant, 'cross_variant', [
    'label' => 'Available in other colors',
    'is_bidirectional' => true,
]);

// Remove relationship
$variant->unrelateFrom($relatedVariant, 'cross_variant');
```

## Use Cases

### Cross-Variant Linking

```php
// Link all color variants together
$redVariant = ProductVariant::where('sku', 'TSH-RED-XL')->first();
$blueVariant = ProductVariant::where('sku', 'TSH-BLUE-XL')->first();
$greenVariant = ProductVariant::where('sku', 'TSH-GREEN-XL')->first();

// Create bidirectional relationships
$redVariant->relateTo($blueVariant, 'cross_variant', [
    'label' => 'Available in Blue',
    'is_bidirectional' => true,
]);

$redVariant->relateTo($greenVariant, 'cross_variant', [
    'label' => 'Available in Green',
    'is_bidirectional' => true,
]);

// Display on product page
$availableColors = $redVariant->getCrossVariants();
```

### Replacement Variants

```php
// Mark old variant as replaced
$oldVariant = ProductVariant::find(1);
$newVariant = ProductVariant::find(2);

$oldVariant->relateTo($newVariant, 'replacement', [
    'label' => 'Newer model available',
    'description' => 'This product has been updated',
]);

// Show replacement on old variant page
$replacement = $oldVariant->getReplacements()->first();
```

### Upgrade/Downgrade Variants

```php
// Link basic and premium variants
$basicVariant = ProductVariant::find(1);
$premiumVariant = ProductVariant::find(2);

// Upgrade path
$basicVariant->relateTo($premiumVariant, 'upgrade', [
    'label' => 'Upgrade to Premium',
    'description' => 'Get more features',
]);

// Downgrade path
$premiumVariant->relateTo($basicVariant, 'downgrade', [
    'label' => 'Budget option',
    'description' => 'Save money',
]);

// Display upgrade option
$upgrade = $basicVariant->getUpgrades()->first();
```

### Accessory Variants

```php
// Link phone with case
$phoneVariant = ProductVariant::find(1);
$caseVariant = ProductVariant::find(2);

$phoneVariant->relateTo($caseVariant, 'accessory', [
    'label' => 'Recommended case',
    'description' => 'Protect your phone',
    'sort_order' => 1,
]);

// Display accessories
$accessories = $phoneVariant->getAccessories();
```

### Bundle Components

```php
// Link bundle with components
$bundleVariant = ProductVariant::find(1);
$component1 = ProductVariant::find(2);
$component2 = ProductVariant::find(3);

$bundleVariant->relateTo($component1, 'bundle_component', [
    'label' => 'Included: Component 1',
    'metadata' => ['quantity' => 1],
]);

$bundleVariant->relateTo($component2, 'bundle_component', [
    'label' => 'Included: Component 2',
    'metadata' => ['quantity' => 2],
]);

// Get bundle components
$components = $bundleVariant->getBundleComponents();
```

## Frontend Usage

### Display Cross-Variants

```blade
@php
    $crossVariants = $variant->getCrossVariants();
@endphp

@if($crossVariants->isNotEmpty())
    <div class="cross-variants">
        <h3>Available in other options:</h3>
        <div class="variant-grid">
            @foreach($crossVariants as $crossVariant)
                <a href="{{ route('frontend.variants.show', $crossVariant->id) }}">
                    <img src="{{ $crossVariant->getThumbnailUrl() }}" alt="{{ $crossVariant->getDisplayName() }}">
                    <span>{{ $crossVariant->getDisplayName() }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
```

### Display Upgrades

```blade
@php
    $upgrades = $variant->getUpgrades();
@endphp

@if($upgrades->isNotEmpty())
    <div class="upgrade-suggestion">
        <h3>Upgrade Available</h3>
        @foreach($upgrades as $upgrade)
            <div class="upgrade-card">
                <h4>{{ $upgrade->getDisplayName() }}</h4>
                <p>{{ $upgrade->product->translateAttribute('description') }}</p>
                <a href="{{ route('frontend.variants.show', $upgrade->id) }}" class="btn">
                    Upgrade Now
                </a>
            </div>
        @endforeach
    </div>
@endif
```

### Display Accessories

```blade
@php
    $accessories = $variant->getAccessories();
@endphp

@if($accessories->isNotEmpty())
    <div class="accessories">
        <h3>Recommended Accessories</h3>
        <div class="accessory-grid">
            @foreach($accessories as $accessory)
                <div class="accessory-card">
                    <img src="{{ $accessory->getThumbnailUrl() }}" alt="{{ $accessory->getDisplayName() }}">
                    <h4>{{ $accessory->getDisplayName() }}</h4>
                    <p>{{ $accessory->product->translateAttribute('name') }}</p>
                    <div class="price">{{ $accessory->getEffectivePrice()->formatted }}</div>
                    <a href="{{ route('frontend.variants.show', $accessory->id) }}" class="btn">
                        Add to Cart
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif
```

## Best Practices

1. **Use bidirectional relationships** for cross-variants (both variants link to each other)
2. **Set meaningful labels** for better UX
3. **Use sort_order** to control display order
4. **Include metadata** for additional context
5. **Auto-generate cross-variants** for same product variants
6. **Use active flag** to temporarily disable relationships
7. **Group relationships** by type for organized display
8. **Validate relationship types** before creating
9. **Prevent self-relationships** (variant cannot relate to itself)
10. **Clean up relationships** when variants are deleted

## Notes

- **Bidirectional relationships**: Automatically creates reverse relationship
- **Self-relationships**: Prevented (variant cannot relate to itself)
- **Duplicate prevention**: Unique constraint on variant_id, related_variant_id, relationship_type
- **Active flag**: Use to temporarily disable relationships
- **Sort order**: Controls display order within relationship type
- **Metadata**: Store additional information (JSON)
- **Auto-generation**: Can auto-generate cross-variants for same product



