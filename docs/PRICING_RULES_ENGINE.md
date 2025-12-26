# Pricing Rules Engine

Comprehensive pricing rules system with priority-based resolution and condition support.

## Overview

Pricing rules are resolved in strict priority order:

1. **Manual override price** - Highest priority
2. **Contract price** (B2B / customer-specific)
3. **Customer group price**
4. **Channel price**
5. **Time-based promotional price**
6. **Tiered (quantity) price**
7. **Base variant price** - Lowest priority

The first valid rule wins (configurable).

## Pricing Rule Types

Each rule has:
- **Priority** - Higher priority = applied first
- **Scope** - What the rule applies to
- **Conditions** - When the rule applies
- **Validity window** - Time-based validity
- **Stackable / non-stackable flag** - Can rules be combined

## Supported Rule Types

### Fixed Price

Set a fixed price for variants matching conditions.

```php
PricingRule::create([
    'name' => 'Fixed Price for VIP Customers',
    'handle' => 'vip-fixed-price',
    'rule_type' => 'fixed_price',
    'priority' => 100,
    'scope_type' => 'customer_group',
    'customer_group_id' => $vipGroup->id,
    'rule_config' => [
        'price' => 5000, // $50.00 in cents
    ],
    'conditions' => [
        'min_quantity' => 1,
    ],
    'is_stackable' => false,
]);
```

### Percentage Discount

Apply a percentage discount.

```php
PricingRule::create([
    'name' => '10% Off for First-Time Buyers',
    'handle' => 'first-time-10-percent',
    'rule_type' => 'percentage_discount',
    'priority' => 200,
    'scope_type' => 'global',
    'rule_config' => [
        'percentage' => 10,
        'min_price' => 1000, // Minimum price after discount
    ],
    'conditions' => [
        'first_time_buyer' => true,
    ],
    'is_stackable' => false,
]);
```

### Absolute Discount

Apply a fixed discount amount.

```php
PricingRule::create([
    'name' => '$5 Off Orders Over $100',
    'handle' => 'five-dollar-off',
    'rule_type' => 'absolute_discount',
    'priority' => 150,
    'scope_type' => 'global',
    'rule_config' => [
        'discount_amount' => 500, // $5.00 in cents
        'min_price' => 0,
    ],
    'conditions' => [
        'min_cart_subtotal' => 10000, // $100.00
    ],
    'is_stackable' => true,
]);
```

### Cost-Plus Pricing

Price based on cost plus margin.

```php
PricingRule::create([
    'name' => 'Cost Plus 20% Margin',
    'handle' => 'cost-plus-20',
    'rule_type' => 'cost_plus',
    'priority' => 300,
    'scope_type' => 'category',
    'category_id' => $category->id,
    'rule_config' => [
        'margin_percentage' => 20,
        // OR
        'margin_amount' => 500, // Fixed margin amount
    ],
    'is_stackable' => false,
]);
```

### Margin-Protected Pricing

Ensure minimum margin is maintained.

```php
PricingRule::create([
    'name' => 'Protect 15% Margin',
    'handle' => 'protect-margin-15',
    'rule_type' => 'margin_protected',
    'priority' => 50,
    'scope_type' => 'global',
    'rule_config' => [
        'min_margin_percentage' => 15,
    ],
    'is_stackable' => false,
]);
```

### MAP Enforcement

Enforce Minimum Advertised Price.

```php
PricingRule::create([
    'name' => 'MAP Enforcement',
    'handle' => 'map-enforcement',
    'rule_type' => 'map_enforcement',
    'priority' => 10,
    'scope_type' => 'product',
    'product_id' => $product->id,
    'rule_config' => [
        'map_price' => 5000, // $50.00 MAP
    ],
    'is_stackable' => false,
]);
```

### Rounding Rules

Round prices to specific increments.

```php
PricingRule::create([
    'name' => 'Round to Nearest Dollar',
    'handle' => 'round-to-dollar',
    'rule_type' => 'rounding',
    'priority' => 500,
    'scope_type' => 'global',
    'rule_config' => [
        'method' => 'nearest', // round, round_up, round_down, nearest
        'round_to' => 100, // Round to nearest 100 cents ($1.00)
    ],
    'is_stackable' => true,
]);
```

### Currency-Specific Overrides

Override prices for specific currencies.

```php
PricingRule::create([
    'name' => 'EUR Price Override',
    'handle' => 'eur-override',
    'rule_type' => 'currency_override',
    'priority' => 400,
    'scope_type' => 'global',
    'currency_id' => $eur->id,
    'rule_config' => [
        'price' => 4500, // Fixed EUR price
        // OR
        'exchange_rate' => 0.85, // Apply exchange rate
    ],
    'conditions' => [
        'currency_ids' => [$eur->id],
    ],
    'is_stackable' => false,
]);
```

## Rule Conditions

Rules may depend on:

### Variant ID / Product ID

```php
'conditions' => [
    'variant_ids' => [1, 2, 3],
    'product_ids' => [10, 20, 30],
],
```

### Category / Collection

```php
'conditions' => [
    'category_ids' => [5, 6, 7],
    'collection_ids' => [8, 9, 10],
],
```

### Customer Group

```php
'conditions' => [
    'customer_group_ids' => [1, 2], // B2B groups
],
```

### Individual Customer

```php
'conditions' => [
    'customer_ids' => [100, 200], // Specific customers
],
```

### Channel

```php
'conditions' => [
    'channel_ids' => [1], // Web channel only
],
```

### Country / Region

```php
'conditions' => [
    'countries' => ['US', 'CA', 'MX'],
    'regions' => ['CA', 'NY', 'TX'], // US states
],
```

### Currency

```php
'conditions' => [
    'currency_ids' => [1, 2], // USD, EUR
],
```

### Quantity (Tiers)

```php
'conditions' => [
    'min_quantity' => 10,
    'max_quantity' => 50,
    // OR
    'quantity_tiers' => [
        ['min' => 1, 'max' => 10],
        ['min' => 11, 'max' => 50],
        ['min' => 51, 'max' => null], // 51+
    ],
],
```

### Date / Time

```php
'conditions' => [
    'date_from' => '2025-01-01',
    'date_to' => '2025-12-31',
    'time_from' => '09:00',
    'time_to' => '17:00',
    'day_of_week' => [1, 2, 3, 4, 5], // Monday-Friday
],
```

### Cart Subtotal

```php
'conditions' => [
    'min_cart_subtotal' => 10000, // $100.00
    'max_cart_subtotal' => 50000, // $500.00
],
```

### Stock Level

```php
'conditions' => [
    'min_stock_level' => 10,
    'max_stock_level' => 100,
    'stock_status' => ['in_stock', 'low_stock'],
],
```

### First-Time Buyer Flag

```php
'conditions' => [
    'first_time_buyer' => true, // Only for first-time buyers
],
```

## Usage

### PriorityPricingResolver

```php
use App\Services\PriorityPricingResolver;

$resolver = app(PriorityPricingResolver::class);

$priceData = $resolver->resolvePrice(
    $variant,
    $quantity = 1,
    $currency,
    $channel,
    $customerGroup,
    $customer,
    $customPriorityOrder = [] // Optional custom order
);

// Returns:
// [
//     'price' => 5000,
//     'compare_at_price' => 6000,
//     'layer' => 'customer_group',
//     'source' => 'customer_group',
//     'currency' => $currency,
//     'tax_inclusive' => false,
//     'applied_rules' => [...],
//     'original_price' => 6000,
// ]
```

### PricingRuleEngine

```php
use App\Services\PricingRuleEngine;

$ruleEngine = app(PricingRuleEngine::class);

$result = $ruleEngine->applyRules(
    $variant,
    $basePrice = 10000,
    $quantity = 1,
    $currency,
    $channel,
    $customerGroup,
    $customer,
    $context = [
        'cart_subtotal' => 50000,
        'country_code' => 'US',
    ]
);

// Returns:
// [
//     'original_price' => 10000,
//     'final_price' => 8500,
//     'applied_rules' => [...],
//     'total_discount' => 1500,
// ]
```

### Model Methods

```php
// Check if rule applies
$applies = $rule->appliesTo($variant, $context);

// Get applicable rules
$rules = PricingRule::active()
    ->orderedByPriority()
    ->get()
    ->filter(fn($rule) => $rule->appliesTo($variant, $context));
```

## Complete Example

```php
// Create a rule: 15% off for B2B customers buying 10+ units in US
PricingRule::create([
    'name' => 'B2B Bulk Discount',
    'handle' => 'b2b-bulk-15-percent',
    'rule_type' => 'percentage_discount',
    'priority' => 200,
    'scope_type' => 'customer_group',
    'customer_group_id' => $b2bGroup->id,
    'rule_config' => [
        'percentage' => 15,
        'min_price' => 1000,
    ],
    'conditions' => [
        'customer_group_ids' => [$b2bGroup->id],
        'min_quantity' => 10,
        'countries' => ['US'],
    ],
    'starts_at' => now(),
    'ends_at' => now()->addYear(),
    'is_stackable' => false,
    'is_active' => true,
]);

// Resolve price
$resolver = app(PriorityPricingResolver::class);
$priceData = $resolver->resolvePrice(
    $variant,
    $quantity = 20,
    $currency = Currency::where('code', 'USD')->first(),
    $channel = Channel::where('handle', 'web')->first(),
    $customerGroup = $b2bGroup,
    $customer = auth()->user()?->customer,
    [
        'country_code' => 'US',
        'cart_subtotal' => 50000,
    ]
);
```

## Configuration

### Priority Order

```php
// config/lunar/pricing_priority.php
return [
    'priority_order' => [
        'manual_override' => 1000,
        'contract' => 900,
        'customer_group' => 800,
        'channel' => 700,
        'promotional' => 600,
        'tiered' => 500,
        'base' => 100,
    ],
];
```

### Custom Priority Order

```php
$customOrder = [
    'contract' => 1000,
    'customer_group' => 900,
    'manual_override' => 800,
    // ... etc
];

$priceData = $resolver->resolvePrice(
    $variant,
    $quantity,
    $currency,
    $channel,
    $customerGroup,
    $customer,
    $customOrder
);
```

## Best Practices

1. **Set priorities carefully** - Higher priority = checked first
2. **Use non-stackable for overrides** - Prevents conflicts
3. **Use stackable for discounts** - Allow multiple discounts
4. **Set validity windows** - Time-based rules
5. **Test conditions thoroughly** - Ensure rules apply correctly
6. **Monitor rule performance** - Index database properly
7. **Use scopes effectively** - Narrow rule application
8. **Document rules** - Clear naming and descriptions
9. **Version control** - Track rule changes
10. **Cache results** - Performance optimization

## Notes

- **Priority**: Higher numbers = checked first
- **First match wins**: For non-stackable rules
- **Stackable rules**: Can be combined up to max_stack_depth
- **Conditions**: All must match for rule to apply
- **Validity window**: Rules only active within time range
- **Performance**: Rules are cached for performance
- **Flexibility**: Fully configurable priority order


