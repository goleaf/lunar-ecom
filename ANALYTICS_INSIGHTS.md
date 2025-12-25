# Analytics & Insights System

This document describes the comprehensive product analytics and insights system.

## Overview

The system provides:

1. **Views tracking** - Track product views and unique visitors
2. **Conversion rate per product** - Calculate conversion rates
3. **Revenue per product** - Track revenue generated
4. **Variant performance** - Analyze variant-level metrics
5. **Abandoned cart rate** - Track cart abandonment
6. **Stock turnover** - Analyze inventory movement
7. **Price elasticity data** - Advanced price sensitivity analysis
8. **A/B testing support** - Test different product configurations

## Database Structure

### Product Analytics Table

Aggregated daily/weekly/monthly analytics:

```sql
product_id BIGINT (FK to products)
date DATE
period ENUM('daily', 'weekly', 'monthly', 'yearly')
views INT
unique_views INT
orders INT
quantity_sold INT
conversion_rate DECIMAL(5,4)
revenue BIGINT
revenue_discounted BIGINT
average_order_value DECIMAL(10,2)
cart_additions INT
cart_removals INT
abandoned_carts INT
abandoned_cart_rate DECIMAL(5,4)
stock_turnover INT
stock_turnover_rate DECIMAL(8,4)
average_price BIGINT
min_price BIGINT
max_price BIGINT
price_changes INT
wishlist_additions INT
reviews_count INT
average_rating DECIMAL(3,2)
```

### Variant Performance Table

Variant-level analytics:

```sql
product_id BIGINT (FK to products)
variant_id BIGINT (FK to product_variants)
date DATE
period ENUM(...)
views INT
unique_views INT
orders INT
quantity_sold INT
conversion_rate DECIMAL(5,4)
revenue BIGINT
stock_turnover INT
stock_turnover_rate DECIMAL(8,4)
average_price BIGINT
```

### Abandoned Carts Table

Track abandoned cart items:

```sql
cart_id BIGINT (FK to carts)
product_id BIGINT (FK to products)
variant_id BIGINT (FK to product_variants)
user_id BIGINT (FK to users)
session_id VARCHAR
email VARCHAR
quantity INT
price BIGINT
total BIGINT
abandoned_at TIMESTAMP
recovered_at TIMESTAMP
converted_at TIMESTAMP
recovery_emails_sent INT
status ENUM('abandoned', 'recovered', 'converted', 'expired')
```

### Price Elasticity Table

Price sensitivity analysis:

```sql
product_id BIGINT (FK to products)
variant_id BIGINT (FK to product_variants)
old_price BIGINT
new_price BIGINT
price_change_percent DECIMAL(8,4)
price_changed_at TIMESTAMP
sales_before INT
sales_after INT
sales_change_percent DECIMAL(8,4)
revenue_before BIGINT
revenue_after BIGINT
revenue_change_percent DECIMAL(8,4)
price_elasticity DECIMAL(10,4)
days_before INT
days_after INT
analysis_date DATE
context JSON
```

### Product A/B Tests Table

A/B testing configuration:

```sql
name VARCHAR(255)
product_id BIGINT (FK to products)
variant_a_id BIGINT (FK to products)
variant_b_id BIGINT (FK to products)
test_type ENUM('title', 'description', 'image', 'price', 'layout', 'cta', 'custom')
variant_a_config JSON
variant_b_config JSON
traffic_split_a INT (percentage)
traffic_split_b INT (percentage)
status ENUM('draft', 'running', 'paused', 'completed', 'cancelled')
visitors_a INT
visitors_b INT
conversions_a INT
conversions_b INT
conversion_rate_a DECIMAL(5,4)
conversion_rate_b DECIMAL(5,4)
revenue_a DECIMAL(12,2)
revenue_b DECIMAL(12,2)
confidence_level DECIMAL(5,2)
winner ENUM('a', 'b', 'none', 'inconclusive')
```

## Services

### ProductAnalyticsService

Calculates and manages product analytics.

**Location**: `app/Services/ProductAnalyticsService.php`

**Key Methods**:

```php
use App\Services\ProductAnalyticsService;

$service = app(ProductAnalyticsService::class);

// Calculate analytics for date
$analytics = $service->calculateForDate($product, '2025-12-26', 'daily');

// Get conversion rate
$conversionRate = $service->getConversionRate($product, $startDate, $endDate);

// Get revenue
$revenue = $service->getRevenue($product, $startDate, $endDate);

// Aggregate for date range
$analytics = $service->aggregateForDateRange($product, $startDate, $endDate, 'daily');
```

### VariantPerformanceService

Calculates variant-level performance.

**Location**: `app/Services/VariantPerformanceService.php`

**Key Methods**:

```php
use App\Services\VariantPerformanceService;

$service = app(VariantPerformanceService::class);

// Calculate variant performance
$performance = $service->calculateForDate($variant, '2025-12-26', 'daily');

// Get top performing variants
$topVariants = $service->getTopPerformingVariants($product, $startDate, $endDate, 10);

// Get performance summary
$summary = $service->getPerformanceSummary($variant, $startDate, $endDate);
```

### AbandonedCartService

Tracks and manages abandoned carts.

**Location**: `app/Services/AbandonedCartService.php`

**Key Methods**:

```php
use App\Services\AbandonedCartService;

$service = app(AbandonedCartService::class);

// Track abandoned cart
$service->trackAbandonedCart($cart);

// Mark as recovered
$service->markAsRecovered($cart);

// Mark as converted
$service->markAsConverted($cart);

// Get abandoned cart rate
$rate = $service->getAbandonedCartRate($product, $startDate, $endDate);

// Get abandoned carts
$carts = $service->getAbandonedCartsForProduct($product, 50);

// Send recovery email
$service->sendRecoveryEmail($abandonedCart);
```

### PriceElasticityService

Calculates price elasticity.

**Location**: `app/Services/PriceElasticityService.php`

**Key Methods**:

```php
use App\Services\PriceElasticityService;

$service = app(PriceElasticityService::class);

// Calculate elasticity for price change
$elasticity = $service->calculateElasticity(
    $variant,
    $oldPrice,
    $newPrice,
    $priceChangedAt,
    30, // days before
    30  // days after
);

// Get elasticity for product
$data = $service->getElasticityForProduct($product);
// Returns: ['average_elasticity', 'is_elastic', 'recommendation']
```

### ABTestingService

Manages A/B tests.

**Location**: `app/Services/ABTestingService.php`

**Key Methods**:

```php
use App\Services\ABTestingService;

$service = app(ABTestingService::class);

// Create test
$test = $service->createTest($product, [
    'name' => 'Product Title Test',
    'test_type' => 'title',
    'variant_a_config' => ['title' => 'Original Title'],
    'variant_b_config' => ['title' => 'New Title'],
    'traffic_split_a' => 50,
    'traffic_split_b' => 50,
]);

// Start test
$service->startTest($test);

// Get variant for user
$variant = $service->getVariant($test, $userId, $sessionId);

// Record event
$service->recordEvent($test, 'a', 'purchase', $userId, $sessionId, ['revenue' => 99.99]);

// Update results
$service->updateResults($test);

// Complete test
$service->completeTest($test);
```

## Usage Examples

### Views Tracking

Already implemented via `ProductView` model and `TrackProductView` middleware.

```php
use App\Models\ProductView;

// Get view count
$views = ProductView::where('product_id', $product->id)->count();

// Get unique views
$uniqueViews = ProductView::where('product_id', $product->id)
    ->distinct('session_id')
    ->count('session_id');
```

### Conversion Rate

```php
use App\Services\ProductAnalyticsService;

$service = app(ProductAnalyticsService::class);

// Get conversion rate for last 30 days
$conversionRate = $service->getConversionRate(
    $product,
    now()->subDays(30),
    now()
);

echo "Conversion Rate: " . number_format($conversionRate * 100, 2) . "%";
```

### Revenue Per Product

```php
// Get revenue for date range
$revenue = $service->getRevenue(
    $product,
    now()->subDays(30),
    now()
);

echo "Revenue: $" . number_format($revenue / 100, 2);
```

### Variant Performance

```php
use App\Services\VariantPerformanceService;

$variantService = app(VariantPerformanceService::class);

// Get top performing variants
$topVariants = $variantService->getTopPerformingVariants($product, null, null, 5);

foreach ($topVariants as $perf) {
    echo "Variant {$perf->variant_id}: {$perf->total_revenue} revenue\n";
}
```

### Abandoned Cart Rate

```php
use App\Services\AbandonedCartService;

$abandonedService = app(AbandonedCartService::class);

// Get abandoned cart rate
$rate = $abandonedService->getAbandonedCartRate($product, now()->subDays(30), now());

echo "Abandoned Cart Rate: " . number_format($rate * 100, 2) . "%";
```

### Stock Turnover

```php
// Get stock turnover from analytics
$analytics = ProductAnalytics::where('product_id', $product->id)
    ->whereBetween('date', [now()->subDays(30), now()])
    ->get();

$totalTurnover = $analytics->sum('stock_turnover');
$avgTurnoverRate = $analytics->avg('stock_turnover_rate');

echo "Stock Turnover: {$totalTurnover} units";
echo "Average Turnover Rate: " . number_format($avgTurnoverRate, 2);
```

### Price Elasticity

```php
use App\Services\PriceElasticityService;

$elasticityService = app(PriceElasticityService::class);

// Calculate elasticity when price changes
$variant->prices()->first()->update(['price' => 12000]); // $120.00

$elasticity = $elasticityService->calculateElasticity(
    $variant,
    10000, // Old price ($100.00)
    12000, // New price ($120.00)
    now(),
    30,
    30
);

// Get elasticity insights
$insights = $elasticityService->getElasticityForProduct($product);
// Returns: ['average_elasticity', 'is_elastic', 'recommendation']
```

### A/B Testing

```php
use App\Services\ABTestingService;

$abService = app(ABTestingService::class);

// Create A/B test
$test = $abService->createTest($product, [
    'name' => 'Product Image Test',
    'test_type' => 'image',
    'variant_a_config' => ['image_url' => '/images/original.jpg'],
    'variant_b_config' => ['image_url' => '/images/new.jpg'],
    'traffic_split_a' => 50,
    'traffic_split_b' => 50,
    'min_sample_size' => 1000,
    'min_duration_days' => 7,
]);

// Start test
$abService->startTest($test);

// In controller/view - get variant
$variant = $abService->getVariant($test, auth()->id(), session()->getId());

// Show variant A or B based on assignment
if ($variant === 'a') {
    // Show variant A
} else {
    // Show variant B
}

// Track events
$abService->recordEvent($test, $variant, 'view', auth()->id(), session()->getId());
$abService->recordEvent($test, $variant, 'add_to_cart', auth()->id(), session()->getId());
$abService->recordEvent($test, $variant, 'purchase', auth()->id(), session()->getId(), [
    'revenue' => 99.99,
]);

// Update results periodically
$abService->updateResults($test);

// Complete test when done
$abService->completeTest($test);
```

## Scheduled Commands

### Calculate Product Analytics

```bash
php artisan analytics:calculate-products
php artisan analytics:calculate-products --date=2025-12-26
php artisan analytics:calculate-products --product=123
```

Schedule:

```php
$schedule->command('analytics:calculate-products')
    ->daily();
```

### Calculate Variant Performance

```bash
php artisan analytics:calculate-variants
```

Schedule:

```php
$schedule->command('analytics:calculate-variants')
    ->daily();
```

### Process Abandoned Carts

```bash
php artisan analytics:process-abandoned-carts --hours=24
```

Schedule:

```php
$schedule->command('analytics:process-abandoned-carts')
    ->hourly();
```

## Observers

### CartObserver

Automatically tracks abandoned carts and conversions.

**Location**: `app/Observers/CartObserver.php`

### OrderObserver

Marks carts as converted when orders are created.

**Location**: `app/Observers/OrderObserver.php`

## Metrics Explained

### Conversion Rate

Formula: `(Orders / Views) * 100`

- Measures how many views result in purchases
- Higher is better
- Typical range: 1-5%

### Abandoned Cart Rate

Formula: `(Abandoned Carts / Cart Additions) * 100`

- Measures how many carts are abandoned
- Lower is better
- Typical range: 60-80%

### Stock Turnover Rate

Formula: `(Quantity Sold / Average Stock)`

- Measures how quickly inventory moves
- Higher is better
- Indicates inventory efficiency

### Price Elasticity

Formula: `(% Change in Quantity) / (% Change in Price)`

- **< -1**: Elastic (demand sensitive to price)
- **-1 to 0**: Inelastic (demand less sensitive)
- **> 0**: Giffen good (demand increases with price)

## Best Practices

1. **Daily Calculation**: Run analytics calculation daily for accurate metrics
2. **Historical Data**: Keep historical analytics for trend analysis
3. **A/B Testing**: Run tests for minimum duration to ensure statistical significance
4. **Price Elasticity**: Analyze over sufficient time periods (30+ days)
5. **Abandoned Carts**: Send recovery emails within 24 hours
6. **Performance**: Use aggregated analytics tables for fast queries
7. **Real-time**: Use event tracking for real-time metrics

## API Endpoints

### Analytics

```php
GET /api/products/{id}/analytics?start_date=2025-12-01&end_date=2025-12-31
GET /api/products/{id}/conversion-rate
GET /api/products/{id}/revenue
GET /api/products/{id}/abandoned-cart-rate
```

### Variant Performance

```php
GET /api/products/{id}/variants/performance
GET /api/variants/{id}/performance
```

### A/B Testing

```php
POST /api/products/{id}/ab-tests
GET  /api/products/{id}/ab-tests/{testId}/variant
POST /api/ab-tests/{testId}/events
GET  /api/ab-tests/{testId}/results
```

## Notes

- Analytics are aggregated daily for performance
- Views are tracked automatically via middleware
- Abandoned carts are tracked via observers
- Price elasticity requires price change history
- A/B tests require minimum sample sizes for validity
- All metrics support date range filtering

