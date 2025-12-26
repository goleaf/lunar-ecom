# Variant Pricing System

Complete pricing system for variants with all advanced features.

## Overview

Variants control the money. This system provides comprehensive pricing capabilities:

1. **Base Pricing** - Base price per currency, compare-at price, cost price
2. **Channel-Specific Pricing** - Different prices per sales channel
3. **Customer-Group Pricing** - B2B pricing, wholesale pricing
4. **Tiered Pricing** - Quantity breaks, volume discounts
5. **Time-Limited Pricing** - Sales, promotions with date ranges
6. **Tax Configuration** - Tax-inclusive/exclusive flags
7. **Price Rounding** - Configurable rounding rules
8. **MAP Pricing** - Minimum Advertised Price enforcement
9. **Price Locks** - Prevent discounts on specific variants
10. **Discount Overrides** - Variant-level discount settings
11. **Dynamic Pricing Hooks** - ERP, AI, rules engine integration

## Core Features

### Base Price per Currency

```php
use App\Models\VariantPrice;
use App\Models\ProductVariant;
use Lunar\Models\Currency;

$variant = ProductVariant::find(1);
$usd = Currency::where('code', 'USD')->first();
$eur = Currency::where('code', 'EUR')->first();

// Set base price in USD
VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $usd->id,
    'price' => 10000, // $100.00
]);

// Set base price in EUR
VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $eur->id,
    'price' => 9200, // €92.00
]);
```

### Compare-At Price (Strike Price)

```php
VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $usd->id,
    'price' => 10000, // $100.00
    'compare_at_price' => 15000, // $150.00 (strike-through price)
]);
```

### Cost Price (Internal Margin Tracking)

```php
$variant->update([
    'cost_price' => 6000, // $60.00 cost
]);

// Calculate margin
$price = $variant->getPrice()['price'];
$cost = $variant->cost_price;
$margin = $price - $cost;
$marginPercentage = ($margin / $price) * 100;
```

### Channel-Specific Pricing

```php
use Lunar\Models\Channel;

$webChannel = Channel::where('handle', 'web')->first();
$mobileChannel = Channel::where('handle', 'mobile')->first();

// Web price
VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $usd->id,
    'channel_id' => $webChannel->id,
    'price' => 10000,
]);

// Mobile price (lower)
VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $usd->id,
    'channel_id' => $mobileChannel->id,
    'price' => 9500,
]);
```

### Customer-Group Pricing (B2B)

```php
use Lunar\Models\CustomerGroup;

$retailGroup = CustomerGroup::where('handle', 'retail')->first();
$wholesaleGroup = CustomerGroup::where('handle', 'wholesale')->first();

// Retail price
VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $usd->id,
    'customer_group_id' => $retailGroup->id,
    'price' => 10000,
]);

// Wholesale price (lower)
VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $usd->id,
    'customer_group_id' => $wholesaleGroup->id,
    'price' => 8000,
]);
```

### Tiered Pricing (Quantity Breaks)

```php
// Tier 1: 1-10 units @ $100
VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $usd->id,
    'price' => 10000,
    'min_quantity' => 1,
    'max_quantity' => 10,
]);

// Tier 2: 11-50 units @ $90
VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $usd->id,
    'price' => 9000,
    'min_quantity' => 11,
    'max_quantity' => 50,
]);

// Tier 3: 51+ units @ $80
VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $usd->id,
    'price' => 8000,
    'min_quantity' => 51,
    'max_quantity' => null, // No max
]);
```

### Time-Limited Pricing (Sales)

```php
use Carbon\Carbon;

VariantPrice::create([
    'variant_id' => $variant->id,
    'currency_id' => $usd->id,
    'price' => 7500, // Sale price: $75.00
    'compare_at_price' => 10000, // Original: $100.00
    'starts_at' => Carbon::now(),
    'ends_at' => Carbon::now()->addDays(7), // 7-day sale
]);
```

### Tax-Inclusive / Exclusive Flags

```php
// Tax-inclusive price
$variant->update([
    'tax_inclusive' => true,
    'price_override' => 10000, // $100.00 includes tax
]);

// Tax-exclusive price (default)
$variant->update([
    'tax_inclusive' => false,
    'price_override' => 10000, // $100.00 + tax
]);
```

### Price Rounding Rules

```php
// Round to nearest $10
$variant->update([
    'price_rounding_rules' => [
        'method' => 'nearest',
        'nearest' => 1000, // $10.00
    ],
]);

// Round up to nearest $5
$variant->update([
    'price_rounding_rules' => [
        'method' => 'round_up',
        'precision' => 0,
    ],
]);

// Round to 2 decimal places
$variant->update([
    'price_rounding_rules' => [
        'method' => 'round',
        'precision' => 2,
    ],
]);
```

## Advanced Features

### Variant-Level Discount Overrides

```php
$variant->update([
    'discount_override' => [
        'discount_type' => 'percentage', // fixed, percentage, override
        'discount_amount' => 10, // 10% discount
        'channels' => [1, 2], // Only for specific channels
        'customer_groups' => [1], // Only for specific customer groups
    ],
]);
```

### MAP Pricing Enforcement

```php
// Set MAP price
$variant->update([
    'map_price' => 8000, // Minimum advertised price: $80.00
]);

// MAP is automatically enforced - prices cannot go below MAP
$price = $variant->getPrice()['price']; // Will be at least $80.00
```

### Price Locks (Cannot Be Discounted)

```php
// Lock price - prevents all discounts
$variant->lockPrice();

// Or set directly
$variant->update([
    'price_locked' => true,
]);

// Check if locked
if ($variant->isPriceLocked()) {
    // Price cannot be discounted
}

// Unlock price
$variant->unlockPrice();
```

### Dynamic Pricing Hooks

#### ERP Integration

```php
use App\Models\VariantPriceHook;

VariantPriceHook::create([
    'variant_id' => $variant->id,
    'hook_type' => 'erp',
    'hook_identifier' => 'sap',
    'config' => [
        'endpoint' => 'https://sap.example.com/api/pricing',
        'product_code' => $variant->sku,
    ],
    'priority' => 10,
    'cache_duration' => 3600, // Cache for 1 hour
]);
```

#### AI Pricing Hook

```php
VariantPriceHook::create([
    'variant_id' => $variant->id,
    'hook_type' => 'ai',
    'hook_identifier' => 'dynamic_pricing',
    'config' => [
        'model' => 'price_optimization_v1',
        'factors' => ['demand', 'competition', 'inventory'],
    ],
    'priority' => 5,
    'cache_duration' => 1800, // Cache for 30 minutes
]);
```

#### Rules Engine Hook

```php
VariantPriceHook::create([
    'variant_id' => $variant->id,
    'hook_type' => 'rules_engine',
    'hook_identifier' => 'drools',
    'config' => [
        'rule_set' => 'pricing_rules.drl',
        'context' => ['season', 'inventory_level'],
    ],
    'priority' => 8,
]);
```

## Service Usage

### Calculate Price

```php
use App\Services\VariantPricingService;
use App\Models\ProductVariant;
use Lunar\Models\Currency;
use Lunar\Models\Channel;
use Lunar\Models\CustomerGroup;

$service = app(VariantPricingService::class);
$variant = ProductVariant::find(1);
$currency = Currency::where('code', 'USD')->first();
$channel = Channel::where('handle', 'web')->first();
$customerGroup = CustomerGroup::where('handle', 'wholesale')->first();

$price = $service->calculatePrice(
    $variant,
    quantity: 5,
    currency: $currency,
    channel: $channel,
    customerGroup: $customerGroup,
    includeTax: false
);

// Returns:
// [
//     'price' => 8000,
//     'price_decimal' => 80.00,
//     'base_price' => 8000,
//     'compare_at_price' => 10000,
//     'cost_price' => 6000,
//     'margin' => 2000,
//     'margin_percentage' => 25.00,
//     'tax_inclusive' => false,
//     'formatted_price' => '$80.00',
//     'quantity' => 5,
//     'currency' => 'USD',
//     'price_locked' => false,
//     'map_price' => 8000,
// ]
```

### Get Tiered Pricing

```php
$tiers = $service->getTieredPricing(
    $variant,
    currency: $currency,
    channel: $channel,
    customerGroup: $customerGroup
);

// Returns collection of tiers:
// [
//     ['min_quantity' => 1, 'max_quantity' => 10, 'price' => 10000, ...],
//     ['min_quantity' => 11, 'max_quantity' => 50, 'price' => 9000, ...],
//     ['min_quantity' => 51, 'max_quantity' => null, 'price' => 8000, ...],
// ]
```

### Set Price

```php
$service->setPrice($variant, [
    'currency_id' => $usd->id,
    'price' => 10000,
    'compare_at_price' => 15000,
    'channel_id' => $webChannel->id,
    'customer_group_id' => $retailGroup->id,
    'min_quantity' => 1,
    'max_quantity' => 10,
    'starts_at' => Carbon::now(),
    'ends_at' => Carbon::now()->addDays(7),
    'tax_inclusive' => false,
    'priority' => 0,
]);
```

## Model Methods

### ProductVariant Methods

```php
// Get price
$price = $variant->getPrice(
    quantity: 5,
    currency: $currency,
    channel: $channel,
    customerGroup: $customerGroup,
    includeTax: false
);

// Get tiered pricing
$tiers = $variant->getTieredPricing($currency, $channel, $customerGroup);

// Check if price is locked
if ($variant->isPriceLocked()) {
    // Cannot be discounted
}

// Lock/unlock price
$variant->lockPrice();
$variant->unlockPrice();
```

## Price Priority

Prices are matched in this order:

1. **Dynamic pricing hooks** (highest priority)
2. **Channel + Customer Group + Quantity** match
3. **Channel + Quantity** match
4. **Customer Group + Quantity** match
5. **Quantity** match only
6. **Base price** (fallback)
7. **Lunar pricing system** (final fallback)

## Configuration

**Config File**: `config/lunar/pricing.php`

```php
return [
    'hooks' => [
        'erp' => [
            'sap' => \App\Services\PricingHooks\SapPricingHook::class,
        ],
        'ai' => [
            'dynamic_pricing' => \App\Services\PricingHooks\AIDynamicPricingHook::class,
        ],
    ],
    'default_rounding' => [
        'method' => 'none',
        'precision' => 0,
        'nearest' => 100,
    ],
    'enforce_map' => true,
    'price_lock_prevents_discounts' => true,
    'default_tax_inclusive' => false,
    'hook_cache_duration' => 3600,
];
```

## Best Practices

1. **Set base prices** for all currencies
2. **Use channel-specific pricing** for multi-channel stores
3. **Implement tiered pricing** for volume discounts
4. **Set MAP prices** to protect brand value
5. **Lock prices** for products that should never be discounted
6. **Use time-limited pricing** for sales and promotions
7. **Configure rounding rules** for consistent pricing
8. **Cache hook prices** to reduce API calls
9. **Set cost prices** for margin tracking
10. **Use compare-at prices** to show savings

## Notes

- All prices stored in smallest currency unit (cents)
- Prices are matched by priority (highest first)
- MAP pricing is automatically enforced
- Price locks prevent all discounts
- Dynamic hooks can override all other pricing
- Tiered pricing supports quantity breaks
- Time-limited pricing automatically expires
- Tax calculation respects tax_inclusive flag


