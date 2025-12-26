# Pricing Engine Standard Output

## Overview

The pricing engine (`UnifiedPricingEngine`) **always** returns a standardized output format with the following required fields:

1. **Final price** - The final price after all rules and discounts
2. **Original price** - The original price before any discounts
3. **Discount breakdown** - Detailed breakdown of all discounts applied
4. **Applied rules** - All pricing rules that were applied
5. **Tax base** - Tax calculation details
6. **Currency metadata** - Complete currency information

## Standard Output Format

```php
[
    // Required Fields
    'final_price' => int,              // Final price after all rules
    'original_price' => int,           // Original price before discounts
    'discount_breakdown' => array,     // Discount breakdown (see below)
    'applied_rules' => array,          // Applied rules (see below)
    'tax_base' => array,               // Tax base (see below)
    'currency_metadata' => array,       // Currency metadata (see below)
    
    // Additional Context
    'pricing_layer' => string,         // Which pricing layer was used
    'pricing_source' => string,        // Source of the price
    'compare_at_price' => int|null,    // Compare-at/strike-through price
    'quantity' => int,                 // Quantity used for calculation
    'variant_id' => int,               // Variant ID
]
```

## Field Details

### discount_breakdown

```php
[
    'total_discount' => int,                    // Total discount amount
    'total_discount_percentage' => float,      // Total discount percentage
    'discounts' => [
        [
            'rule_id' => int|null,
            'rule_name' => string|null,
            'rule_type' => string,
            'price_before' => int,
            'price_after' => int,
            'adjustment' => int,
            'adjustment_percentage' => float,
        ],
        // ... more discounts
    ],
]
```

### applied_rules

```php
[
    [
        'id' => int|null,
        'name' => string|null,
        'handle' => string|null,
        'type' => string,
        'priority' => int,
        'is_stackable' => bool,
        'scope' => string|null,
        'conditions' => array,
        'price_before' => int,
        'price_after' => int,
        'adjustment' => int,
    ],
    // ... more rules
]
```

### tax_base

```php
[
    'base_price' => int,              // Price before tax
    'tax_amount' => int,              // Total tax amount
    'total_price' => int,             // Price including tax
    'tax_inclusive' => bool,           // Whether price includes tax
    'tax_breakdown' => [
        [
            'name' => string,
            'percentage' => float,
            'amount' => int,
        ],
        // ... more tax rates
    ],
    'total_tax_rate' => float,        // Total tax rate percentage
]
```

### currency_metadata

```php
[
    'id' => int,
    'code' => string,                 // ISO currency code (e.g., 'USD')
    'name' => string,                 // Currency name (e.g., 'US Dollar')
    'symbol' => string,               // Currency symbol (e.g., '$')
    'exchange_rate' => float,          // Exchange rate
    'decimal_places' => int,          // Number of decimal places
    'is_default' => bool,             // Whether this is the default currency
    'formatted_symbol' => string,     // Formatted symbol
]
```

## Usage

### Basic Usage

```php
use App\Services\UnifiedPricingEngine;

$engine = app(UnifiedPricingEngine::class);

$result = $engine->calculatePrice(
    variant: $variant,
    quantity: 1,
    currency: $currency,
    channel: $channel,
    customerGroup: $customerGroup,
    customer: $customer
);

// All fields are always present
$finalPrice = $result['final_price'];
$originalPrice = $result['original_price'];
$discountBreakdown = $result['discount_breakdown'];
$appliedRules = $result['applied_rules'];
$taxBase = $result['tax_base'];
$currencyMetadata = $result['currency_metadata'];
```

### Price Simulation

```php
// Simulate price without committing
$simulation = $engine->simulatePrice(
    variant: $variant,
    quantity: 10,
    currency: $currency
);

// Same format as calculatePrice, plus:
// - 'is_simulation' => true
// - 'simulated_at' => ISO8601 timestamp
```

### Price Locked Variants

When a variant has `price_locked = true`, the output format remains the same, but:
- `final_price` equals `original_price` (no discounts applied)
- `discount_breakdown['total_discount']` is 0
- `applied_rules` is empty array
- `price_locked` => true is added to the result

## Integration

### Frontend

```php
class ProductController extends Controller
{
    public function show(ProductVariant $variant)
    {
        $engine = app(UnifiedPricingEngine::class);
        
        $pricing = $engine->calculatePrice(
            variant: $variant,
            quantity: 1,
            currency: Currency::default()->first(),
            channel: Channel::default()->first(),
            customerGroup: auth()->user()?->customerGroups->first(),
            customer: auth()->user()?->customer
        );

        return view('product.show', [
            'variant' => $variant,
            'pricing' => $pricing,
        ]);
    }
}
```

### Cart Calculation

```php
foreach ($cart->lines as $line) {
    $pricing = $engine->calculatePrice(
        variant: $line->purchasable,
        quantity: $line->quantity,
        currency: $cart->currency,
        channel: $cart->channel,
        customerGroup: $cart->customer?->customerGroups->first(),
        customer: $cart->customer
    );

    // All fields available
    $line->final_price = $pricing['final_price'];
    $line->original_price = $pricing['original_price'];
    $line->total_discount = $pricing['discount_breakdown']['total_discount'];
    $line->tax_amount = $pricing['tax_base']['tax_amount'];
}
```

### API Response

```php
class PricingController extends Controller
{
    public function getPrice(ProductVariant $variant, Request $request)
    {
        $engine = app(UnifiedPricingEngine::class);
        
        $pricing = $engine->calculatePrice(
            variant: $variant,
            quantity: $request->input('quantity', 1),
            currency: Currency::find($request->input('currency_id')),
            channel: Channel::find($request->input('channel_id')),
            customerGroup: CustomerGroup::find($request->input('customer_group_id')),
            customer: Customer::find($request->input('customer_id'))
        );

        return response()->json($pricing);
    }
}
```

## Guarantees

The `UnifiedPricingEngine` guarantees:

1. ✅ **Always returns all 6 required fields** - No missing fields
2. ✅ **Consistent structure** - Same format regardless of pricing layer
3. ✅ **Complete discount breakdown** - Every discount is detailed
4. ✅ **Full rule traceability** - All applied rules are included
5. ✅ **Tax calculation** - Tax base always calculated
6. ✅ **Currency metadata** - Complete currency information

## Error Handling

If no price is found, the engine throws `\RuntimeException` with message:
- "No price found for variant."
- "No currency available for price calculation."

Always handle these exceptions:

```php
try {
    $pricing = $engine->calculatePrice($variant);
} catch (\RuntimeException $e) {
    // Handle error
    return response()->json(['error' => $e->getMessage()], 404);
}
```

## Migration from Old Pricing Services

If you're using `VariantPricingService` or `PriorityPricingResolver` directly, migrate to `UnifiedPricingEngine`:

### Before

```php
$service = app(VariantPricingService::class);
$price = $service->calculatePrice($variant);
// Inconsistent output format
```

### After

```php
$engine = app(UnifiedPricingEngine::class);
$pricing = $engine->calculatePrice($variant);
// Standardized output format with all fields
```

The `VariantPricingService` now uses `UnifiedPricingEngine` internally, so existing code will automatically get the standardized output.


