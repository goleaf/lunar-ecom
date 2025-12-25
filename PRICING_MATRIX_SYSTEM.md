# Product Variants Pricing Matrix System

## Overview

The Pricing Matrix System provides advanced variant pricing capabilities including:
- **Quantity-based tiered pricing** (volume discounts)
- **Customer group pricing** (retail, wholesale, VIP)
- **Regional pricing** (different prices per country/region)
- **Mixed pricing rules** (complex conditions combining multiple factors)
- **Promotional pricing** (date-based pricing)
- **Mix-and-match pricing** (tier pricing across variants)
- **Price history tracking**
- **Pricing approval workflow** (for wholesale accounts)
- **Bulk import/export** for pricing updates
- **Comprehensive pricing reports**

## Models

### PriceMatrix

Stores pricing rules for products with support for multiple matrix types:

```php
use App\Models\PriceMatrix;

// Create quantity-based pricing
PriceMatrix::create([
    'product_id' => 1,
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

// Create customer group pricing
PriceMatrix::create([
    'product_id' => 1,
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

// Create regional pricing
PriceMatrix::create([
    'product_id' => 1,
    'matrix_type' => PriceMatrix::TYPE_REGION,
    'rules' => [
        'regions' => [
            'US' => ['price' => 10000],
            'EU' => ['price' => 9000],
            'UK' => ['price' => 8500],
        ]
    ],
    'is_active' => true,
]);

// Create mixed pricing with complex conditions
PriceMatrix::create([
    'product_id' => 1,
    'matrix_type' => PriceMatrix::TYPE_MIXED,
    'rules' => [
        'conditions' => [
            [
                'quantity' => ['min' => 11, 'max' => 50],
                'customer_group' => 'wholesale',
                'region' => 'US',
                'price' => 7500,
            ],
            [
                'quantity' => ['min' => 51],
                'customer_group' => 'wholesale',
                'price' => 7000,
            ],
        ]
    ],
    'is_active' => true,
    'priority' => 1, // Higher priority rules apply first
]);

// Promotional pricing with date range
PriceMatrix::create([
    'product_id' => 1,
    'matrix_type' => PriceMatrix::TYPE_QUANTITY,
    'rules' => [
        'tiers' => [
            ['min_quantity' => 1, 'price' => 8000], // 20% off
        ]
    ],
    'starts_at' => '2024-12-01 00:00:00',
    'ends_at' => '2024-12-31 23:59:59',
    'is_active' => true,
    'priority' => 10, // Higher priority for promotional pricing
]);
```

### PriceHistory

Tracks all price changes for audit and reporting:

```php
use App\Models\PriceHistory;
use App\Services\MatrixPricingService;

$service = app(MatrixPricingService::class);

// Track price change
$service->trackPriceChange(
    $variant,
    $oldPrice = 10000,
    $newPrice = 9000,
    PriceHistory::TYPE_UPDATED,
    $matrix,
    auth()->id(),
    'Bulk price update for holiday season'
);
```

### PricingApproval

Manages approval workflow for wholesale pricing:

```php
use App\Models\PricingApproval;

// Request approval
$approval = PricingApproval::create([
    'price_matrix_id' => $matrix->id,
    'customer_group_id' => $wholesaleGroup->id,
    'status' => PricingApproval::STATUS_PENDING,
    'requested_changes' => [
        'old_price' => 10000,
        'new_price' => 8500,
    ],
    'requested_by' => auth()->id(),
    'requested_at' => now(),
]);

// Approve
$approval->approve(auth()->id(), 'Approved for Q1 2024');

// Reject
$approval->reject(auth()->id(), 'Price too low, margin concerns');
```

## Services

### MatrixPricingService

Main service for calculating prices:

```php
use App\Services\MatrixPricingService;
use App\Models\ProductVariant;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;

$service = app(MatrixPricingService::class);

// Calculate price
$price = $service->calculatePrice(
    $variant,
    $quantity = 25,
    $currency = Currency::where('default', true)->first(),
    $customerGroup = CustomerGroup::where('handle', 'wholesale')->first(),
    $region = 'US'
);

// Result:
// [
//     'price' => 9000,  // in cents
//     'price_decimal' => 90.00,
//     'formatted_price' => '$90.00',
//     'savings_percentage' => 10.0,
//     'you_save' => 'Save 10%',
//     ...
// ]

// Get tiered pricing
$tiers = $service->getTieredPricing($variant, $currency, $customerGroup, $region);

// Get volume discounts
$discounts = $service->getVolumeDiscounts($variant, $currency, $customerGroup);
```

### PricingReportService

Generate comprehensive pricing reports:

```php
use App\Services\PricingReportService;

$reportService = app(PricingReportService::class);

// Summary report
$summary = $reportService->generateSummaryReport();

// Report by product
$productReport = $reportService->reportByProduct($productId = 1);

// Report by customer group
$customerGroupReport = $reportService->reportByCustomerGroup('wholesale');

// Report by region
$regionReport = $reportService->reportByRegion('US');

// Price history report
$historyReport = $reportService->reportPriceHistory([
    'product_id' => 1,
    'date_from' => '2024-01-01',
    'date_to' => '2024-12-31',
]);
```

## Commands

### Import Pricing Matrix

Import pricing matrices from CSV or JSON:

```bash
# Import from CSV
php artisan pricing:import storage/app/pricing.csv --format=csv

# Import from JSON
php artisan pricing:import storage/app/pricing.json --format=json

# Dry run (test without saving)
php artisan pricing:import storage/app/pricing.csv --dry-run
```

**CSV Format:**
```csv
product_id,matrix_type,rules,is_active,priority,starts_at,ends_at,description
1,quantity,"{""tiers"":[{""min_quantity"":1,""max_quantity"":10,""price"":10000}]}",1,0,,
```

**JSON Format:**
```json
[
  {
    "product_id": 1,
    "matrix_type": "quantity",
    "rules": {
      "tiers": [
        {"min_quantity": 1, "max_quantity": 10, "price": 10000},
        {"min_quantity": 11, "max_quantity": 50, "price": 9000},
        {"min_quantity": 51, "price": 8000}
      ]
    },
    "is_active": true,
    "priority": 0
  }
]
```

### Export Pricing Matrix

Export pricing matrices to CSV or JSON:

```bash
# Export all matrices
php artisan pricing:export --format=csv

# Export for specific product
php artisan pricing:export --product=1 --format=json

# Export specific type
php artisan pricing:export --type=quantity --format=csv

# Specify output file
php artisan pricing:export --output=storage/app/pricing_export.csv
```

## API Endpoints

### Calculate Price

```http
POST /api/pricing/calculate
Content-Type: application/json

{
    "variant_id": 1,
    "quantity": 25,
    "currency_id": 1,
    "customer_group": "wholesale",
    "region": "US"
}
```

### Get Tiered Pricing

```http
GET /api/pricing/tiers?variant_id=1&customer_group=wholesale&region=US
```

### Get Volume Discounts

```http
GET /api/pricing/volume-discounts?variant_id=1&customer_group=wholesale
```

### Pricing Reports (Admin)

```http
GET /api/reports/pricing/summary
GET /api/reports/pricing/by-product?product_id=1
GET /api/reports/pricing/by-customer-group?customer_group=wholesale
GET /api/reports/pricing/by-region?region=US
GET /api/reports/pricing/price-history?product_id=1&date_from=2024-01-01
```

## Frontend Components

### Tier Pricing Table

Display tier pricing table on product pages:

```blade
<x-tier-pricing-table 
    :variant="$variant" 
    :currency="$currency"
    :customerGroup="$customerGroup"
    :region="$region"
    :showSavings="true"
    :highlightCurrent="true"
    :currentQuantity="1"
/>
```

### Pricing Calculator

Interactive pricing calculator with quantity selector:

```blade
<x-pricing-calculator 
    :variant="$variant" 
    :currency="$currency"
    :customerGroup="$customerGroup"
    :region="$region"
/>
```

## Usage in Product Variant Model

The ProductVariant model has helper methods:

```php
$variant = ProductVariant::find(1);

// Get price using matrix pricing
$price = $variant->getMatrixPrice(
    $quantity = 25,
    $currency,
    $customerGroup,
    $region = 'US'
);

// Get tiered pricing
$tiers = $variant->getTieredPricing($currency, $customerGroup, $region);

// Get volume discounts
$discounts = $variant->getVolumeDiscounts($currency, $customerGroup);
```

## Mix-and-Match Pricing

Support tier pricing across multiple variants:

```php
$service = app(MatrixPricingService::class);

$price = $service->calculatePrice(
    $variant,
    $quantity = 10,
    $currency,
    $customerGroup,
    $region,
    $variantQuantities = [
        1 => 5,  // variant_id => quantity
        2 => 3,
        3 => 2,
    ]
);
```

The service will calculate the total quantity (10) and apply the appropriate tier pricing.

## Minimum Order Quantities

Set minimum order quantities per price tier:

```php
PriceMatrix::create([
    'product_id' => 1,
    'matrix_type' => PriceMatrix::TYPE_QUANTITY,
    'rules' => [
        'tiers' => [
            [
                'min_quantity' => 1,
                'max_quantity' => 10,
                'price' => 10000,
                'min_order_quantity' => 1,  // Minimum order for this tier
            ],
            [
                'min_quantity' => 11,
                'max_quantity' => 50,
                'price' => 9000,
                'min_order_quantity' => 11,  // Must order at least 11
            ],
        ]
    ],
]);
```

## Best Practices

1. **Priority Management**: Use higher priority values for promotional pricing that should override base pricing
2. **Date Ranges**: Always set `starts_at` and `ends_at` for promotional pricing to avoid conflicts
3. **Price History**: Always track price changes using `MatrixPricingService::trackPriceChange()`
4. **Approval Workflow**: Use `PricingApproval` for wholesale pricing changes that require review
5. **Testing**: Use `--dry-run` flag when importing pricing to test before applying changes
6. **Reports**: Regularly generate pricing reports to monitor pricing strategy effectiveness

## Database Schema

- `lunar_price_matrices`: Stores pricing rules
- `lunar_price_histories`: Tracks price changes
- `lunar_pricing_approvals`: Manages approval workflow

Run migrations:

```bash
php artisan migrate
```

## Examples

See `database/seeders/PricingMatrixSeeder.php` for example pricing matrix setups.

