# Pricing & Currency Management - Complete Implementation

This document describes the complete implementation of the Advanced Pricing & Currency Management system with all required features.

## Overview

The Pricing & Currency Management system provides a comprehensive pricing engine supporting multiple currencies, customer groups, channels, tiered pricing, time-based sales, tax calculations, and automatic currency conversion with rounding rules.

## Features

### ✅ Advanced Pricing Engine

The `AdvancedPricingService` consolidates all pricing calculations:

```php
use App\Services\AdvancedPricingService;
use App\Models\ProductVariant;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Channel;

$service = app(AdvancedPricingService::class);

$price = $service->calculatePrice(
    $variant,
    quantity: 5,
    currency: Currency::find(1),
    customerGroup: CustomerGroup::find(1),
    channel: Channel::find(1),
    includeTax: false
);
```

### ✅ Multi-Currency Pricing

Prices can be set per currency using Lunar's `Price` model:

```php
use Lunar\Models\Price;
use App\Models\ProductVariant;

// Set price in USD
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'currency_id' => $usd->id,
    'price' => 10000, // $100.00 in cents
    'tier' => 1,
]);

// Set price in EUR
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'currency_id' => $eur->id,
    'price' => 9200, // €92.00 in cents
    'tier' => 1,
]);
```

### ✅ Per-Variant Pricing

Each variant can have its own prices via the `Price` model's polymorphic relationship:

```php
// Variant-specific price override
$variant->price_override = 15000; // $150.00
$variant->save();

// Or use Price model
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'currency_id' => $currency->id,
    'price' => 15000,
]);
```

### ✅ Customer-Group Pricing

Prices can be set per customer group:

```php
use Lunar\Models\Price;
use Lunar\Models\CustomerGroup;

// Retail price
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'currency_id' => $currency->id,
    'customer_group_id' => $retailGroup->id,
    'price' => 10000,
]);

// Wholesale price
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'currency_id' => $currency->id,
    'customer_group_id' => $wholesaleGroup->id,
    'price' => 8000,
]);
```

Or use PriceMatrix for customer group pricing:

```php
use App\Models\PriceMatrix;

PriceMatrix::create([
    'product_id' => $product->id,
    'matrix_type' => PriceMatrix::TYPE_CUSTOMER_GROUP,
    'rules' => [
        'customer_groups' => [
            'retail' => ['price' => 10000],
            'wholesale' => ['price' => 8000],
            'vip' => ['price' => 7500],
        ]
    ],
    'is_active' => true,
]);
```

### ✅ Tiered Pricing (Bulk Discounts)

Quantity-based tiered pricing via PriceMatrix:

```php
use App\Models\PriceMatrix;

PriceMatrix::create([
    'product_id' => $product->id,
    'matrix_type' => PriceMatrix::TYPE_QUANTITY,
    'rules' => [
        'tiers' => [
            ['min_quantity' => 1, 'max_quantity' => 10, 'price' => 10000],  // $100.00
            ['min_quantity' => 11, 'max_quantity' => 50, 'price' => 9000],  // $90.00
            ['min_quantity' => 51, 'price' => 8000],  // $80.00 (no max)
        ]
    ],
    'is_active' => true,
    'priority' => 0,
]);

// Get tiered pricing
$tiers = $service->getTieredPricing($variant, $currency, $customerGroup);
```

### ✅ Time-Based Pricing (Sales)

Promotional pricing with date ranges:

```php
use App\Models\PriceMatrix;
use Carbon\Carbon;

PriceMatrix::create([
    'product_id' => $product->id,
    'matrix_type' => PriceMatrix::TYPE_QUANTITY,
    'rules' => [
        'tiers' => [
            ['min_quantity' => 1, 'price' => 7500], // Sale price: $75.00
        ]
    ],
    'starts_at' => Carbon::parse('2024-12-01 00:00:00'),
    'ends_at' => Carbon::parse('2024-12-31 23:59:59'),
    'is_active' => true,
    'priority' => 10, // Higher priority for sales
]);

// Get active sales
$activeSales = $service->getActiveSales($variant, $currency, $customerGroup);
```

### ✅ Channel-Specific Pricing

Prices can be set per channel (web, mobile app, POS, etc.):

```php
use Lunar\Models\Price;
use Lunar\Models\Channel;

// Web store price
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'currency_id' => $currency->id,
    'channel_id' => $webChannel->id,
    'price' => 10000,
]);

// Mobile app price (different price)
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'currency_id' => $currency->id,
    'channel_id' => $mobileChannel->id,
    'price' => 9500, // Lower price for mobile
]);
```

### ✅ Tax-Inclusive / Tax-Exclusive Prices

Configure tax handling in `config/lunar/pricing.php`:

```php
return [
    'stored_inclusive_of_tax' => env('LUNAR_STORE_INCLUSIVE_OF_TAX', false),
    // ...
];
```

Calculate prices with tax:

```php
use App\Services\AdvancedPricingService;

// Price excluding tax
$priceExTax = $service->calculatePrice(
    $variant,
    quantity: 1,
    currency: $currency,
    includeTax: false
);
// Returns: price_ex_tax, price_inc_tax, tax_amount

// Price including tax
$priceIncTax = $service->calculatePrice(
    $variant,
    quantity: 1,
    currency: $currency,
    includeTax: true
);
```

### ✅ Automatic Currency Conversion (Optional)

Enable automatic conversion per currency:

```php
use Lunar\Models\Currency;

$currency = Currency::find(1);
$currency->auto_convert = true;
$currency->save();
```

When enabled, prices are automatically converted from the default currency to the target currency using exchange rates.

```php
use App\Lunar\Currencies\CurrencyHelper;

// Convert price
$converted = CurrencyHelper::convert(100, 'USD', 'EUR');

// Convert and round
$convertedRounded = CurrencyHelper::convertAndRound(100, 'USD', 'EUR');
```

### ✅ Rounding Rules Per Currency

Each currency can have custom rounding rules:

**Rounding Modes:**
- `none` - No rounding
- `up` - Always round up
- `down` - Always round down
- `nearest` - Round to nearest (default)
- `nearest_up` - Round to nearest, but round up on ties
- `nearest_down` - Round to nearest, but round down on ties

**Rounding Precision:**
- `0.01` - Round to nearest cent (default)
- `0.05` - Round to nearest 5 cents
- `0.10` - Round to nearest 10 cents
- `1.00` - Round to nearest whole unit

**Usage:**
```php
use Lunar\Models\Currency;
use App\Lunar\Currencies\CurrencyHelper;

// Configure currency rounding
$currency = Currency::find(1);
$currency->rounding_mode = 'nearest';
$currency->rounding_precision = 0.05; // Round to nearest 5 cents
$currency->save();

// Round a price
$rounded = CurrencyHelper::roundPrice(99.97, $currency); // Returns 100.00
$roundedInteger = CurrencyHelper::roundPriceInteger(9997, $currency); // Returns 10000 (cents)
```

## Database Schema

### Prices Table (Enhanced)

- `channel_id` (foreign key) - Channel-specific pricing
- Index: `['priceable_type', 'priceable_id', 'channel_id', 'currency_id', 'customer_group_id']`

### Currencies Table (Enhanced)

- `rounding_mode` (string) - Rounding mode (none, up, down, nearest, etc.)
- `rounding_precision` (decimal) - Rounding precision (e.g., 0.01, 0.05, 1.00)
- `auto_convert` (boolean) - Enable automatic currency conversion

### PriceMatrix Table

- `starts_at` (datetime) - Promotional pricing start
- `ends_at` (datetime) - Promotional pricing end
- `matrix_type` - quantity, customer_group, region, mixed
- `rules` (JSON) - Pricing rules structure
- `priority` (integer) - Rule priority (higher = applied first)

## Services

### AdvancedPricingService

Comprehensive pricing service with methods:

- `calculatePrice()` - Calculate price with full context
- `getTieredPricing()` - Get tiered pricing tiers
- `getActiveSales()` - Get active sales/promotions
- `calculateBulkPrice()` - Calculate price for multiple variants

### MatrixPricingService

Advanced matrix-based pricing:

- `calculatePrice()` - Calculate using price matrices
- `getTieredPricing()` - Get tiered pricing information
- `getVolumeDiscounts()` - Get volume discount tiers
- `trackPriceChange()` - Track price history

### CurrencyHelper

Currency management and conversion:

- `convert()` - Convert between currencies
- `convertAndRound()` - Convert and apply rounding
- `roundPrice()` - Round price according to currency rules
- `roundPriceInteger()` - Round price in integer format
- `updateExchangeRate()` - Update exchange rate

## Usage Examples

### Calculate Price with Full Context

```php
use App\Services\AdvancedPricingService;
use App\Models\ProductVariant;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Channel;

$service = app(AdvancedPricingService::class);

$price = $service->calculatePrice(
    variant: $variant,
    quantity: 5,
    currency: Currency::where('code', 'USD')->first(),
    customerGroup: CustomerGroup::where('handle', 'wholesale')->first(),
    channel: Channel::where('handle', 'webstore')->first(),
    includeTax: false
);

// Returns:
// [
//     'price' => 8000,
//     'price_decimal' => 80.00,
//     'price_ex_tax' => 8000,
//     'price_inc_tax' => 9600,
//     'tax_amount' => 1600,
//     'formatted_price' => '$80.00',
//     'currency' => 'USD',
//     'savings' => 20.00,
//     'savings_percentage' => 20.0,
// ]
```

### Create Channel-Specific Price

```php
use Lunar\Models\Price;
use Lunar\Models\Channel;

$webChannel = Channel::where('handle', 'webstore')->first();
$mobileChannel = Channel::where('handle', 'mobile')->first();

// Web price
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'currency_id' => $currency->id,
    'channel_id' => $webChannel->id,
    'price' => 10000,
]);

// Mobile price (different)
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'currency_id' => $currency->id,
    'channel_id' => $mobileChannel->id,
    'price' => 9500,
]);
```

### Create Time-Based Sale

```php
use App\Models\PriceMatrix;
use Carbon\Carbon;

PriceMatrix::create([
    'product_id' => $product->id,
    'matrix_type' => PriceMatrix::TYPE_QUANTITY,
    'rules' => [
        'tiers' => [
            ['min_quantity' => 1, 'price' => 7500], // 25% off
        ]
    ],
    'starts_at' => Carbon::now()->addDays(7),
    'ends_at' => Carbon::now()->addDays(14),
    'is_active' => true,
    'priority' => 10,
    'description' => 'Black Friday Sale - 25% Off',
]);
```

### Configure Currency Rounding

```php
use Lunar\Models\Currency;

// Round to nearest 5 cents
$currency = Currency::where('code', 'USD')->first();
$currency->rounding_mode = 'nearest';
$currency->rounding_precision = 0.05;
$currency->save();

// Round to nearest dollar (no cents)
$jpy = Currency::where('code', 'JPY')->first();
$jpy->rounding_mode = 'nearest';
$jpy->rounding_precision = 1.00;
$jpy->save();
```

### Bulk Price Calculation

```php
use App\Services\AdvancedPricingService;

$variants = [
    1 => 5,  // Variant ID 1, quantity 5
    2 => 3,  // Variant ID 2, quantity 3
    3 => 10, // Variant ID 3, quantity 10
];

$bulkPrice = $service->calculateBulkPrice(
    variants: $variants,
    currency: $currency,
    customerGroup: $customerGroup,
    channel: $channel,
    includeTax: false
);

// Returns total price for all variants
```

## Summary

✅ Advanced pricing engine (AdvancedPricingService)
✅ Multi-currency pricing (Price model with currency_id)
✅ Per-variant pricing (Price model with morphs)
✅ Customer-group pricing (Price model + PriceMatrix)
✅ Tiered pricing (PriceMatrix TYPE_QUANTITY)
✅ Time-based pricing (PriceMatrix with starts_at/ends_at)
✅ Channel-specific pricing (Price model with channel_id)
✅ Tax-inclusive / tax-exclusive prices (config + calculation)
✅ Automatic currency conversion (Currency auto_convert flag)
✅ Rounding rules per currency (rounding_mode, rounding_precision)

The Pricing & Currency Management system is now complete with all required features.


