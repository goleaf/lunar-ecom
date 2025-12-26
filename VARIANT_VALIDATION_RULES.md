# Variant Validation & Rules Engine

Complete validation and rules engine for preventing bad data.

## Overview

Comprehensive validation system to prevent bad data:

1. **SKU uniqueness rules** - Ensure unique SKUs
2. **Attribute combination validation** - Prevent duplicate combinations
3. **Stock constraints** - Validate stock limits
4. **Price sanity checks** - Validate pricing logic
5. **Shipping eligibility rules** - Weight/dimension limits
6. **Channel availability rules** - Channel restrictions
7. **Country restrictions** - Geo-restrictions
8. **Customer-group restrictions** - B2B restrictions

## Validation Types

### Basic Validation

- **SKU Uniqueness** - Ensures SKU is unique across all variants
- **Attribute Combination** - Prevents duplicate attribute combinations per product
- **Stock Constraints** - Validates min/max quantities, backorder limits
- **Price Sanity** - Validates price relationships (compare-at, MAP, cost)

### Rules Engine

- **Shipping Eligibility** - Weight/dimension limits, special handling requirements
- **Channel Availability** - Allowed/blocked channels
- **Country Restrictions** - Allowed/blocked countries
- **Customer-Group Restrictions** - Allowed/blocked customer groups

## Usage

### VariantValidationService

```php
use App\Services\VariantValidationService;

$service = app(VariantValidationService::class);
```

### Basic Validation

```php
// Validate variant
$errors = $service->validate($variant, [
    'sku' => 'NEW-SKU',
    'stock' => 100,
    'price' => 5000,
]);

// Check if valid
if (empty($errors)) {
    // Variant is valid
}

// Validate or throw exception
$service->validateOrFail($variant, $data);
```

### SKU Uniqueness

```php
// Validate SKU uniqueness
$errors = $service->validateSkuUniqueness($variant, 'NEW-SKU');

if (!empty($errors)) {
    // SKU already exists
}
```

### Attribute Combination Validation

```php
// Validate attribute combination
$errors = $service->validateAttributeCombination($variant, [
    'product_id' => 1,
    'option_values' => [1, 2, 3], // Option value IDs
]);

if (!empty($errors)) {
    // Duplicate combination exists
}
```

### Stock Constraints

```php
// Validate stock constraints
$errors = $service->validateStockConstraints($variant, [
    'min_order_quantity' => 1,
    'max_order_quantity' => 10,
    'stock' => 100,
    'backorder_limit' => 50,
]);

// Validates:
// - Min <= Max
// - Backorder <= Backorder limit
// - Low stock threshold >= 0
```

### Price Sanity Checks

```php
// Validate price sanity
$errors = $service->validatePriceSanity($variant, [
    'price' => 5000,
    'compare_at_price' => 6000,
    'cost_price' => 3000,
    'map_price' => 4500,
]);

// Validates:
// - Compare-at price > regular price
// - Price >= MAP price (if enforced)
// - Price >= 0
// - Warns if price < cost price
```

### Shipping Eligibility

```php
// Validate shipping eligibility
$errors = $service->validateShippingEligibility($variant, [
    'max_weight' => 5000, // grams
    'max_dimensions' => [
        'length' => 100,
        'width' => 50,
        'height' => 50,
    ],
]);

// Checks:
// - Weight limits
// - Dimension limits
// - Special handling requirements
```

### Channel Availability

```php
// Validate channel availability
$errors = $service->validateChannelAvailability($variant, $channelId);

// Checks:
// - Blocked channels
// - Allowed channels (whitelist)
// - Variant visibility settings
```

### Country Restrictions

```php
// Validate country restrictions
$errors = $service->validateCountryRestrictions($variant, 'US');

// Checks:
// - Blocked countries
// - Allowed countries (whitelist)
// - Product-level restrictions
```

### Customer-Group Restrictions

```php
// Validate customer-group restrictions
$errors = $service->validateCustomerGroupRestrictions($variant, $customerGroupId);

// Checks:
// - Blocked customer groups
// - Allowed customer groups (whitelist)
```

### Context Validation

```php
// Validate for complete context
$errors = $service->validateForContext($variant, [
    'sku' => 'NEW-SKU',
    'channel_id' => 1,
    'country_code' => 'US',
    'customer_group_id' => 2,
    'shipping' => [
        'max_weight' => 5000,
    ],
]);

// Check if valid
$isValid = $service->isValidForContext($variant, $context);
```

## Rules Engine

### VariantRulesEngine

```php
use App\Services\VariantRulesEngine;

$engine = app(VariantRulesEngine::class);
```

### Create Rules

```php
// Create shipping eligibility rule
$engine->createShippingEligibilityRule($variant, [
    'max_weight' => 5000, // grams
    'max_dimensions' => [
        'length' => 100,
        'width' => 50,
        'height' => 50,
    ],
    'require_special_handling' => true,
]);

// Create channel availability rule
$engine->createChannelAvailabilityRule(
    $variant,
    $allowedChannels = [1, 2], // Whitelist
    $blockedChannels = [3]     // Blacklist
);

// Create country restriction rule
$engine->createCountryRestrictionRule(
    $variant,
    $allowedCountries = ['US', 'CA', 'MX'], // Whitelist
    $blockedCountries = []                  // Blacklist
);

// Create customer-group restriction rule
$engine->createCustomerGroupRestrictionRule(
    $variant,
    $allowedGroups = [1, 2], // Whitelist
    $blockedGroups = []      // Blacklist
);
```

### Get Rules

```php
// Get all rules
$rules = $engine->getRules($variant);

// Get specific rule type
$shippingRules = $engine->getRules($variant, 'shipping_eligibility');
```

### Delete Rules

```php
// Delete rule
$engine->deleteRule($ruleId);
```

## Model Methods

### ProductVariant Methods

```php
// Validate variant
$errors = $variant->validate($context);

// Check if valid
$isValid = $variant->isValidForContext($context);

// Validate or throw exception
$variant->validateOrFail($context);

// Check availability
$isAvailable = $variant->isAvailableInChannel($channelId);
$isAvailable = $variant->isAvailableInCountry('US');
$isAvailable = $variant->isAvailableForCustomerGroup($groupId);
$isEligible = $variant->isEligibleForShipping($shippingContext);

// Get validation rules
$rules = $variant->validationRules;
```

## Usage Examples

### Create Variant with Validation

```php
// Create variant with validation
$variant = new ProductVariant([
    'product_id' => 1,
    'sku' => 'TSH-RED-XL',
    'stock' => 100,
    'price' => 5000,
]);

// Validate before saving
$errors = $variant->validate([
    'option_values' => [1, 2], // Color: Red, Size: XL
]);

if (empty($errors)) {
    $variant->save();
} else {
    // Handle errors
    foreach ($errors as $error) {
        echo $error . "\n";
    }
}
```

### Add Shipping Eligibility Rule

```php
// Add shipping eligibility rule
$engine->createShippingEligibilityRule($variant, [
    'max_weight' => 5000, // 5kg max
    'max_dimensions' => [
        'length' => 100, // cm
        'width' => 50,
        'height' => 50,
    ],
]);

// Validate shipping
$errors = $service->validateShippingEligibility($variant);
```

### Add Country Restrictions

```php
// Block variant in certain countries
$engine->createCountryRestrictionRule(
    $variant,
    $allowedCountries = [], // Empty = all allowed
    $blockedCountries = ['CN', 'RU'] // Blocked countries
);

// Check availability
if ($variant->isAvailableInCountry('US')) {
    // Available in US
}

if (!$variant->isAvailableInCountry('CN')) {
    // Not available in China
}
```

### Add Channel Restrictions

```php
// Restrict to specific channels
$engine->createChannelAvailabilityRule(
    $variant,
    $allowedChannels = [1, 2], // Web and Mobile only
    $blockedChannels = []
);

// Check availability
if ($variant->isAvailableInChannel(1)) {
    // Available in channel 1
}
```

### Add Customer-Group Restrictions

```php
// B2B only variant
$engine->createCustomerGroupRestrictionRule(
    $variant,
    $allowedGroups = [2], // B2B group only
    $blockedGroups = []
);

// Check availability
if ($variant->isAvailableForCustomerGroup(2)) {
    // Available for B2B customers
}
```

### Complete Context Validation

```php
// Validate variant for complete context
$context = [
    'channel_id' => 1,
    'country_code' => 'US',
    'customer_group_id' => 2,
    'shipping' => [
        'max_weight' => 5000,
    ],
];

$errors = $variant->validateForContext($context);

if (empty($errors)) {
    // Variant is valid for this context
    // Can be added to cart, displayed, etc.
}
```

## Frontend Usage

### Check Availability Before Display

```php
// In controller
$variant = ProductVariant::find($id);
$channelId = $currentChannel->id;
$countryCode = $request->get('country', 'US');
$customerGroupId = auth()->user()?->customer_group_id;

// Validate context
if (!$variant->isValidForContext([
    'channel_id' => $channelId,
    'country_code' => $countryCode,
    'customer_group_id' => $customerGroupId,
])) {
    abort(404, 'Variant not available');
}

// Display variant
return view('variants.show', compact('variant'));
```

### Cart Validation

```php
// Before adding to cart
$variant = ProductVariant::find($request->variant_id);

try {
    $variant->validateOrFail([
        'channel_id' => $currentChannel->id,
        'country_code' => $shippingAddress->country,
        'customer_group_id' => auth()->user()?->customer_group_id,
    ]);

    // Add to cart
    $cart->add($variant, $quantity);
} catch (ValidationException $e) {
    return back()->withErrors($e->errors());
}
```

## Best Practices

1. **Validate on save** - Automatic validation in model events
2. **Validate before display** - Check availability before showing variant
3. **Validate in cart** - Check restrictions before adding to cart
4. **Use rules engine** - Create reusable validation rules
5. **Set priorities** - Higher priority rules checked first
6. **Active flag** - Temporarily disable rules
7. **Whitelist vs blacklist** - Use allowed_values for whitelist, restrictions for blacklist
8. **Context validation** - Validate for complete context (channel, country, customer group)
9. **Error messages** - Provide clear error messages
10. **Performance** - Cache validation results when possible

## Notes

- **Automatic validation**: Runs on model save events
- **SKU uniqueness**: Enforced across all variants
- **Attribute combinations**: Enforced per product
- **Price validation**: MAP enforcement configurable
- **Rules priority**: Higher priority rules checked first
- **Active flag**: Rules can be temporarily disabled
- **Context-aware**: Validates based on channel, country, customer group
- **Error collection**: Returns array of error messages


