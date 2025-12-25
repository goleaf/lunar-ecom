# Variant Physical Properties & Shipping

Complete physical properties and shipping system for logistics.

## Overview

Critical for logistics. This system provides comprehensive physical properties and shipping information:

1. **Weight** - Actual weight in grams
2. **Dimensions** - Length, width, height (via Lunar's dimensions)
3. **Volumetric Weight** - Calculated from dimensions
4. **Shipping Class** - Standard, express, oversized, fragile, etc.
5. **Fragile Flag** - Requires special handling
6. **Hazardous Flag** - Hazardous materials classification
7. **Country of Origin** - For customs
8. **HS/Customs Codes** - Harmonized System codes
9. **Lead Time** - Production delay in days

## Physical Properties

### Weight

```php
// Set weight in grams
$variant->update([
    'weight' => 1500, // 1.5 kg
]);

// Get weight
$weight = $variant->weight; // 1500 grams

// Get weight in different units
$service = app(VariantShippingService::class);
$weightKg = $service->getShippingWeightInKg($variant); // 1.5
$weightLbs = $service->getShippingWeightInLbs($variant); // 3.31
```

### Dimensions

```php
// Set dimensions (via Lunar's dimensions method)
$variant->update([
    'dimensions' => [
        'length' => 30.5, // cm
        'width' => 20.0,  // cm
        'height' => 15.0, // cm
    ],
]);

// Get dimensions
$dimensions = $service->getDimensions($variant);
// Returns: ['length' => 30.5, 'width' => 20.0, 'height' => 15.0]

// Get dimensions in inches
$dimensionsInches = $service->getDimensionsInInches($variant);
```

### Volumetric Weight

Volumetric weight is calculated from dimensions:

**Formula**: `(length × width × height) / divisor`

**Default divisor**: 5000 (cm³ per kg)

```php
// Volumetric weight is auto-calculated when dimensions change
$variant->update([
    'dimensions' => [
        'length' => 30.5,
        'width' => 20.0,
        'height' => 15.0,
    ],
]);

// Calculate volumetric weight
$volumetricWeight = $service->calculateVolumetricWeight($variant);
// (30.5 × 20.0 × 15.0) / 5000 = 1.83 kg = 1830 grams

// Custom volumetric divisor
$variant->update([
    'volumetric_divisor' => 4000, // Different divisor for this variant
]);

// Shipping weight = max(actual_weight, volumetric_weight)
$shippingWeight = $service->getShippingWeight($variant);
```

### Volume

```php
// Get volume in cubic centimeters
$volume = $service->getVolume($variant);
// Returns: 9150 cm³

// Get volume in cubic meters
$volumeM3 = $service->getVolumeInCubicMeters($variant);
// Returns: 0.00915 m³
```

## Shipping Properties

### Shipping Class

```php
// Set shipping class
$variant->update([
    'shipping_class' => 'standard', // standard, express, oversized, fragile, etc.
]);

// Common shipping classes:
// - 'standard'
// - 'express'
// - 'oversized'
// - 'fragile'
// - 'hazardous'
// - 'frozen'
// - 'refrigerated'
```

### Fragile Flag

```php
// Mark as fragile
$variant->update([
    'is_fragile' => true,
]);

// Check if fragile
if ($variant->isFragile()) {
    // Apply fragile handling
}
```

### Hazardous Flag

```php
// Mark as hazardous
$variant->update([
    'is_hazardous' => true,
    'hazardous_class' => 'Class 3', // Flammable liquids
]);

// Check if hazardous
if ($variant->isHazardous()) {
    // Apply hazardous handling
    $hazardousClass = $variant->hazardous_class;
}

// Common hazardous classes:
// - Class 1: Explosives
// - Class 2: Gases
// - Class 3: Flammable liquids
// - Class 4: Flammable solids
// - Class 5: Oxidizing substances
// - Class 6: Toxic substances
// - Class 7: Radioactive materials
// - Class 8: Corrosive substances
// - Class 9: Miscellaneous dangerous goods
```

### Special Handling

```php
// Check if requires special handling
if ($variant->requiresSpecialHandling()) {
    // Fragile or hazardous
}

// Get shipping requirements
$requirements = $variant->getShippingRequirements();
// Returns:
// [
//     'shipping_class' => 'fragile',
//     'is_fragile' => true,
//     'is_hazardous' => false,
//     'hazardous_class' => null,
//     'requires_special_handling' => true,
//     'shipping_weight' => 1500,
//     'shipping_weight_kg' => 1.5,
//     'volumetric_weight' => 1830,
//     'dimensions' => ['length' => 30.5, 'width' => 20.0, 'height' => 15.0],
// ]
```

## Customs & International Shipping

### Country of Origin

```php
// Set origin country (variant-level override)
$variant->update([
    'origin_country' => 'CN', // ISO 2-character code
]);

// Get origin country (variant-level or product-level)
$originCountry = $variant->getOriginCountry();
```

### HS Code (Harmonized System Code)

```php
// Set HS code
$variant->update([
    'hs_code' => '8471.30.01', // Computer equipment
]);

// Get HS code
$hsCode = $variant->getHSCode();

// Common HS code formats:
// - 6-digit: Basic classification
// - 8-digit: Country-specific
// - 10-digit: Detailed classification
```

### Customs Description

```php
// Set customs description
$variant->update([
    'customs_description' => 'Electronic tablet computer, 10.2 inch display',
]);

// Get customs information
$customsInfo = $variant->getCustomsInfo();
// Returns:
// [
//     'hs_code' => '8471.30.01',
//     'origin_country' => 'CN',
//     'customs_description' => 'Electronic tablet computer...',
//     'weight' => 1500,
//     'value' => 50000, // Price in cents
// ]
```

## Lead Time (Production Delay)

```php
// Set lead time in days
$variant->update([
    'lead_time_days' => 14, // 2 weeks production delay
]);

// Get lead time information
$leadTime = $variant->getLeadTimeInfo();
// Returns:
// [
//     'lead_time_days' => 14,
//     'lead_time_weeks' => 2.0,
//     'estimated_ship_date' => Carbon instance,
//     'is_made_to_order' => true,
// ]

// Get lead time in days
$days = $variant->getLeadTimeDays(); // 14

// Check if made to order
if ($leadTime['is_made_to_order']) {
    // Show "Made to order" message
    // Estimated ship date: {$leadTime['estimated_ship_date']->format('M d, Y')}
}
```

## Service Usage

### VariantShippingService

```php
use App\Services\VariantShippingService;

$service = app(VariantShippingService::class);
```

#### Calculate Volumetric Weight

```php
$volumetricWeight = $service->calculateVolumetricWeight($variant);
// Returns: weight in grams
```

#### Get Shipping Weight

```php
// Shipping weight = max(actual_weight, volumetric_weight)
$shippingWeight = $service->getShippingWeight($variant);
$shippingWeightKg = $service->getShippingWeightInKg($variant);
$shippingWeightLbs = $service->getShippingWeightInLbs($variant);
```

#### Get Dimensions

```php
$dimensions = $service->getDimensions($variant);
$dimensionsInches = $service->getDimensionsInInches($variant);
```

#### Get Volume

```php
$volume = $service->getVolume($variant); // cm³
$volumeM3 = $service->getVolumeInCubicMeters($variant); // m³
```

#### Get Shipping Requirements

```php
$requirements = $service->getShippingRequirements($variant);
```

#### Get Customs Info

```php
$customsInfo = $service->getCustomsInfo($variant);
```

#### Get Lead Time Info

```php
$leadTime = $service->getLeadTimeInfo($variant);
```

#### Update Volumetric Weight

```php
// Update volumetric weight for variant
$service->updateVolumetricWeight($variant);

// Bulk update
$variants = ProductVariant::whereNotNull('dimensions')->get();
$updated = $service->bulkUpdateVolumetricWeights($variants);
```

## Model Methods

### ProductVariant Methods

```php
// Get shipping weight
$shippingWeight = $variant->getShippingWeight();

// Get volumetric weight
$volumetricWeight = $variant->getVolumetricWeight();

// Get shipping requirements
$requirements = $variant->getShippingRequirements();

// Get customs info
$customsInfo = $variant->getCustomsInfo();

// Get lead time info
$leadTime = $variant->getLeadTimeInfo();

// Check special handling
if ($variant->requiresSpecialHandling()) {
    // Fragile or hazardous
}

// Check fragile
if ($variant->isFragile()) {
    // Handle fragile item
}

// Check hazardous
if ($variant->isHazardous()) {
    // Handle hazardous item
}

// Get origin country
$originCountry = $variant->getOriginCountry();

// Get HS code
$hsCode = $variant->getHSCode();

// Get lead time
$leadTimeDays = $variant->getLeadTimeDays();
```

## Shipping Weight Calculation

Shipping weight is the **greater of actual weight or volumetric weight**:

```php
$actualWeight = $variant->weight; // 1500 grams
$volumetricWeight = $variant->volumetric_weight; // 1830 grams
$shippingWeight = max($actualWeight, $volumetricWeight); // 1830 grams
```

This ensures carriers charge for the space items take up, not just their weight.

## Volumetric Weight Formula

**Standard Formula**:
```
Volumetric Weight (kg) = (Length × Width × Height) / 5000
```

**Custom Divisor**:
```php
$variant->volumetric_divisor = 4000; // Different divisor
```

Common divisors:
- **5000** - Standard (cm³ per kg)
- **4000** - Some carriers
- **6000** - Some regions

## Best Practices

1. **Always set dimensions** for accurate shipping calculations
2. **Set weight** for actual weight-based shipping
3. **Volumetric weight** is auto-calculated when dimensions change
4. **Use shipping class** to categorize items
5. **Mark fragile items** for special handling
6. **Set HS codes** for international shipping
7. **Set origin country** for customs
8. **Set lead time** for made-to-order items
9. **Update volumetric weights** after bulk dimension updates
10. **Use shipping weight** (not actual weight) for shipping calculations

## Integration with Shipping Carriers

```php
// Get shipping requirements for carrier API
$requirements = $variant->getShippingRequirements();

// Send to carrier API
$carrier->calculateShipping([
    'weight' => $requirements['shipping_weight_kg'],
    'dimensions' => $requirements['dimensions'],
    'fragile' => $requirements['is_fragile'],
    'hazardous' => $requirements['is_hazardous'],
    'hazardous_class' => $requirements['hazardous_class'],
    'shipping_class' => $requirements['shipping_class'],
]);

// Get customs info for international shipping
$customsInfo = $variant->getCustomsInfo();

// Include in customs declaration
$customsDeclaration = [
    'hs_code' => $customsInfo['hs_code'],
    'origin_country' => $customsInfo['origin_country'],
    'description' => $customsInfo['customs_description'],
    'weight' => $customsInfo['weight'],
    'value' => $customsInfo['value'],
];
```

## Notes

- **Weight**: Stored in grams (integer)
- **Dimensions**: Stored in centimeters (decimal)
- **Volumetric weight**: Auto-calculated, stored for performance
- **Shipping weight**: Max of actual and volumetric weight
- **HS codes**: 6-10 digit codes for customs classification
- **Origin country**: ISO 2-character code
- **Lead time**: Production delay in days
- **Fragile/Hazardous**: Boolean flags with special handling
- **Shipping class**: Categorizes shipping requirements

