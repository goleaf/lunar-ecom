# Advanced Pricing Features

This document describes the advanced pricing features implemented for the Lunar e-commerce platform.

## Overview

The pricing system provides enterprise-grade pricing capabilities including:
- Time windows (sales, flash deals)
- Price locks (cannot be discounted)
- Scheduled price changes
- Dynamic pricing hooks (ERP, AI, scripts)
- Price simulation (preview final price)
- Margin alerts
- Historical price tracking (legal compliance)

## Unified Pricing Engine

The `UnifiedPricingEngine` service provides a standardized output format for all price calculations. It always returns:

### Standard Output Format

```php
[
    'final_price' => int,              // Final price after all rules
    'original_price' => int,           // Original price before discounts
    'discount_breakdown' => [
        'total_discount' => int,
        'total_discount_percentage' => float,
        'discounts' => [
            [
                'rule_id' => int,
                'rule_name' => string,
                'rule_type' => string,
                'price_before' => int,
                'price_after' => int,
                'adjustment' => int,
                'adjustment_percentage' => float,
            ],
            // ... more discounts
        ],
    ],
    'applied_rules' => [
        [
            'id' => int,
            'name' => string,
            'handle' => string,
            'type' => string,
            'priority' => int,
            'is_stackable' => bool,
            'scope' => string,
            'conditions' => array,
            'price_before' => int,
            'price_after' => int,
            'adjustment' => int,
        ],
        // ... more rules
    ],
    'tax_base' => [
        'base_price' => int,
        'tax_amount' => int,
        'total_price' => int,
        'tax_inclusive' => bool,
        'tax_breakdown' => [
            [
                'name' => string,
                'percentage' => float,
                'amount' => int,
            ],
            // ... more tax rates
        ],
        'total_tax_rate' => float,
    ],
    'currency_metadata' => [
        'id' => int,
        'code' => string,
        'name' => string,
        'symbol' => string,
        'exchange_rate' => float,
        'decimal_places' => int,
        'is_default' => bool,
        'formatted_symbol' => string,
    ],
    'pricing_layer' => string,         // Which pricing layer was used
    'pricing_source' => string,         // Source of the price
    'compare_at_price' => int|null,     // Compare-at/strike-through price
    'quantity' => int,
    'variant_id' => int,
]
```

## Usage

### Basic Price Calculation

```php
use App\Services\UnifiedPricingEngine;
use App\Models\ProductVariant;

$engine = app(UnifiedPricingEngine::class);

$result = $engine->calculatePrice(
    variant: $variant,
    quantity: 1,
    currency: $currency,
    channel: $channel,
    customerGroup: $customerGroup,
    customer: $customer
);

// Access standardized fields
$finalPrice = $result['final_price'];
$originalPrice = $result['original_price'];
$discountBreakdown = $result['discount_breakdown'];
$appliedRules = $result['applied_rules'];
$taxBase = $result['tax_base'];
$currencyMetadata = $result['currency_metadata'];
```

### Price Simulation

```php
// Preview final price without committing
$simulation = $engine->simulatePrice(
    variant: $variant,
    quantity: 10,
    currency: $currency,
    channel: $channel
);

// Same format as calculatePrice, but with:
// - 'is_simulation' => true
// - 'simulated_at' => ISO8601 timestamp
```

## Advanced Features

### Time Windows (Sales, Flash Deals)

```php
use App\Services\AdvancedPricingService;

$service = app(AdvancedPricingService::class);

// Create a flash deal
$flashDeal = $service->createFlashDeal($variant, [
    'currency_id' => $currency->id,
    'price' => 1999, // $19.99
    'compare_at_price' => 2999, // $29.99
    'starts_at' => now(),
    'ends_at' => now()->addHours(24),
    'priority' => 700,
]);

// Create a regular sale
$sale = $service->createTimeWindowPrice($variant, [
    'currency_id' => $currency->id,
    'price' => 2499,
    'starts_at' => now(),
    'ends_at' => now()->addDays(7),
]);
```

### Scheduled Price Changes

```php
// Schedule a price change
$scheduled = $service->schedulePriceChange($variant, [
    'currency_id' => $currency->id,
    'current_price' => 2999,
    'scheduled_price' => 2499,
    'scheduled_change_at' => now()->addDays(7),
]);

// Process scheduled changes (run via cron)
// php artisan pricing:process-scheduled-changes
```

### Price Locks

```php
// Lock price (prevent discounts)
$service->lockPrice($variant, 'MAP pricing enforcement');

// Unlock price
$service->unlockPrice($variant);
```

### Dynamic Pricing Hooks

```php
use App\Models\VariantPriceHook;

// Create a dynamic pricing hook
VariantPriceHook::create([
    'product_variant_id' => $variant->id,
    'hook_service' => 'App\Services\ERP\ERPPricingService',
    'config' => [
        'api_endpoint' => 'https://erp.example.com/api/pricing',
        'api_key' => 'xxx',
    ],
    'priority' => 100,
    'is_active' => true,
    'cache_ttl_minutes' => 60,
]);

// Hook service must implement:
class ERPPricingService
{
    public function calculatePrice(
        ProductVariant $variant,
        int $quantity,
        Currency $currency,
        ?Channel $channel = null,
        ?CustomerGroup $customerGroup = null
    ): int {
        // Call ERP API and return price
        return $erpPrice;
    }
}
```

### Margin Alerts

```php
// Check margin for a variant
$alert = $service->checkMarginAlert($variant, $price, $thresholdMargin);

if ($alert) {
    // Alert created
    echo "Alert: {$alert->message}";
}

// Check all variants (run via cron)
// php artisan pricing:check-margin-alerts
```

### Historical Price Tracking

```php
// Track price change automatically
$service->trackPriceChange($variant, [
    'price' => 2999,
    'change_reason' => 'manual',
    'changed_by' => auth()->id(),
]);

// Get price history
$history = $service->getPriceHistory(
    variant: $variant,
    currency: $currency,
    from: now()->subMonths(6),
    to: now()
);

foreach ($history as $entry) {
    echo "Price: {$entry->price} from {$entry->effective_from} to {$entry->effective_to}";
    echo "Reason: {$entry->change_reason}";
}
```

## Database Tables

### variant_prices
Stores variant pricing with support for:
- Scheduled price changes (`scheduled_change_at`, `scheduled_price`)
- Flash deals (`is_flash_deal`)
- Time windows (`starts_at`, `ends_at`)

### price_simulations
Stores price simulation results for analysis.

### margin_alerts
Tracks margin alerts for variants.

### price_history
Historical price tracking for legal compliance.

## Artisan Commands

### Process Scheduled Price Changes

```bash
php artisan pricing:process-scheduled-changes
```

Runs every minute via cron to process scheduled price changes.

### Check Margin Alerts

```bash
php artisan pricing:check-margin-alerts
php artisan pricing:check-margin-alerts --threshold=15
php artisan pricing:check-margin-alerts --variant=123
```

Runs every 6 hours to check and create margin alerts.

## Configuration

### config/lunar/pricing.php

```php
return [
    'default_rounding_rule' => 'round',
    'default_tax_type' => 'exclusive',
    'enforce_map_pricing' => true,
    'enforce_price_locks' => true,
    'dynamic_pricing_cache_ttl' => 60,
    'margin_alert_threshold' => 10.0, // Percentage
];
```

## Integration Examples

### Frontend Controller

```php
use App\Services\UnifiedPricingEngine;

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

    // Use pricing data for cart line
    $line->final_price = $pricing['final_price'];
    $line->original_price = $pricing['original_price'];
    $line->discount = $pricing['discount_breakdown']['total_discount'];
}
```

## Best Practices

1. **Always use UnifiedPricingEngine** for consistent output format
2. **Track price changes** for legal compliance
3. **Monitor margin alerts** to protect profitability
4. **Use price simulation** before applying changes
5. **Cache dynamic pricing** results appropriately
6. **Process scheduled changes** via cron regularly
7. **Lock prices** when MAP enforcement is required

## Legal Compliance

The historical price tracking feature helps with:
- Price transparency requirements
- Consumer protection laws
- Audit trails for pricing decisions
- Compliance with regional pricing regulations

All price changes are automatically tracked with:
- Timestamp
- User who made the change
- Reason for change
- Effective date range


