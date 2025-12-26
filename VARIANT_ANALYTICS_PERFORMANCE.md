# Variant Analytics & Performance

Complete analytics and performance tracking for variants.

## Overview

Know what sells! Comprehensive variant-level analytics:

1. **Views per variant** - Track variant views
2. **Conversion rate** - Views to orders conversion
3. **Revenue per variant** - Total revenue and discounted revenue
4. **Stock turnover** - How fast inventory moves
5. **Return rate per variant** - Return tracking and analysis
6. **Discount impact** - Impact of discounts on sales
7. **Variant popularity ranking** - Rank variants by performance

## Database Structure

### Variant Views Table

Tracks individual variant views:
- `product_variant_id` - Variant ID
- `session_id` - Session identifier
- `ip_address` - IP address
- `user_agent` - User agent
- `user_id` - User ID (if logged in)
- `channel_id` - Channel ID
- `referrer` - Referrer URL
- `viewed_at` - View timestamp

### Variant Returns Table

Tracks variant returns:
- `product_variant_id` - Variant ID
- `order_id` - Order ID
- `order_line_id` - Order line ID
- `quantity_returned` - Quantity returned
- `refund_amount` - Refund amount
- `return_reason` - Reason for return
- `status` - Return status
- `returned_at` - Return timestamp

### Variant Performance Table (Enhanced)

Aggregated analytics:
- `views` - Total views
- `unique_views` - Unique views
- `orders` - Order count
- `quantity_sold` - Quantity sold
- `conversion_rate` - Conversion rate
- `revenue` - Total revenue
- `revenue_discounted` - Revenue after discounts
- `returns_count` - Return count
- `return_rate` - Return rate percentage
- `return_revenue` - Refunded revenue
- `discount_applied_count` - Orders with discounts
- `discount_amount_total` - Total discount amount
- `discount_impact_revenue` - Estimated revenue impact
- `popularity_score` - Calculated popularity score
- `popularity_rank` - Popularity ranking

## Usage

### VariantAnalyticsService

```php
use App\Services\VariantAnalyticsService;

$service = app(VariantAnalyticsService::class);
```

### Track Views

```php
// Track variant view
$service->trackView($variant, [
    'session_id' => session()->getId(),
    'user_id' => auth()->id(),
    'channel_id' => 1,
]);

// Using model method
$variant->trackView([
    'channel_id' => 1,
]);
```

### Get Views

```php
// Get views for variant
$views = $service->getViews($variant, $startDate, $endDate);

// Returns:
// [
//     'total_views' => 150,
//     'unique_views' => 120,
//     'unique_users' => 80,
// ]

// Using model method
$views = $variant->getViews($startDate, $endDate);
```

### Calculate Conversion Rate

```php
// Calculate conversion rate
$conversionRate = $service->calculateConversionRate($variant, $startDate, $endDate);
// Returns: 2.5 (percentage)

// Using model method
$conversionRate = $variant->getConversionRate($startDate, $endDate);
```

### Calculate Revenue

```php
// Calculate revenue
$revenue = $service->calculateRevenue($variant, $startDate, $endDate);

// Returns:
// [
//     'revenue' => 50000, // cents
//     'revenue_after_discount' => 45000,
//     'discount_total' => 5000,
//     'quantity_sold' => 10,
//     'average_order_value' => 5000,
//     'orders_count' => 8,
// ]

// Using model method
$revenue = $variant->getRevenue($startDate, $endDate);
```

### Calculate Stock Turnover

```php
// Calculate stock turnover
$turnover = $service->calculateStockTurnover($variant, $startDate, $endDate);

// Returns:
// [
//     'quantity_sold' => 50,
//     'average_stock' => 100,
//     'turnover_rate' => 50.0, // percentage
//     'days_to_turnover' => 7.3, // days
// ]

// Using model method
$turnover = $variant->getStockTurnover($startDate, $endDate);
```

### Track Returns

```php
// Track variant return
$service->trackReturn($variant, $orderId, $orderLineId, [
    'quantity' => 1,
    'refund_amount' => 5000,
    'reason' => 'defective',
    'notes' => 'Product was damaged',
    'status' => 'approved',
]);
```

### Calculate Return Rate

```php
// Calculate return rate
$returnRate = $service->calculateReturnRate($variant, $startDate, $endDate);

// Returns:
// [
//     'returns_count' => 2,
//     'quantity_returned' => 2,
//     'return_revenue' => 10000,
//     'quantity_sold' => 50,
//     'return_rate' => 4.0, // percentage
// ]

// Using model method
$returnRate = $variant->getReturnRate($startDate, $endDate);
```

### Calculate Discount Impact

```php
// Calculate discount impact
$discountImpact = $service->calculateDiscountImpact($variant, $startDate, $endDate);

// Returns:
// [
//     'discount_applied_count' => 5,
//     'discount_amount_total' => 5000,
//     'quantity_with_discount' => 8,
//     'discount_impact_revenue' => 15000, // Estimated
//     'average_discount_per_order' => 1000,
// ]

// Using model method
$discountImpact = $variant->getDiscountImpact($startDate, $endDate);
```

### Calculate Popularity Score

```php
// Calculate popularity score
$score = $service->calculatePopularityScore($variant);
// Returns: 1250.5

// Using model method
$score = $variant->getPopularityScore();
```

### Update Popularity Rankings

```php
// Update popularity rankings for all variants
$updated = $service->updatePopularityRankings();
// Returns number of variants updated
```

### Complete Analytics

```php
// Get complete analytics
$analytics = $service->calculateAnalytics($variant, $startDate, $endDate);

// Returns:
// [
//     'views' => [...],
//     'conversion_rate' => 2.5,
//     'revenue' => [...],
//     'stock_turnover' => [...],
//     'return_rate' => [...],
//     'discount_impact' => [...],
//     'popularity_score' => 1250.5,
//     'popularity_rank' => 5,
//     'period' => [...],
// ]

// Using model method
$analytics = $variant->getAnalytics($startDate, $endDate);
```

### Store Analytics

```php
// Store analytics in VariantPerformance table
$performance = $service->storeAnalytics($variant, $date, 'daily');
// Stores aggregated analytics for the date/period
```

## Model Methods

### ProductVariant Methods

```php
// Track view
$variant->trackView($context);

// Get analytics
$analytics = $variant->getAnalytics($startDate, $endDate);

// Get specific metrics
$views = $variant->getViews($startDate, $endDate);
$conversionRate = $variant->getConversionRate($startDate, $endDate);
$revenue = $variant->getRevenue($startDate, $endDate);
$stockTurnover = $variant->getStockTurnover($startDate, $endDate);
$returnRate = $variant->getReturnRate($startDate, $endDate);
$discountImpact = $variant->getDiscountImpact($startDate, $endDate);
$popularityScore = $variant->getPopularityScore();
$popularityRank = $variant->getPopularityRank();

// Relationships
$views = $variant->views;
$returns = $variant->returns;
$performance = $variant->performance;
```

## Popularity Score Formula

```php
// Popularity Score = (views * 0.1) + (revenue * 0.001) + (conversion_rate * 100) - (return_rate * 50)

$score = 
    ($views['total_views'] * 0.1) +
    ($revenue['revenue'] * 0.001) +
    ($conversionRate * 100) -
    ($returnRate['return_rate'] * 50);
```

## Frontend Usage

### Track View on Variant Page

```php
// In controller
public function show(ProductVariant $variant)
{
    // Track view
    $variant->trackView([
        'channel_id' => $currentChannel->id,
    ]);

    // Get analytics
    $analytics = $variant->getAnalytics(
        Carbon::now()->subDays(30),
        Carbon::now()
    );

    return view('variants.show', compact('variant', 'analytics'));
}
```

### Display Analytics

```blade
@php
    $analytics = $variant->getAnalytics(
        now()->subDays(30),
        now()
    );
@endphp

<div class="variant-analytics">
    <h3>Performance Metrics</h3>
    
    <div class="metric">
        <span class="label">Views:</span>
        <span class="value">{{ number_format($analytics['views']['total_views']) }}</span>
    </div>
    
    <div class="metric">
        <span class="label">Conversion Rate:</span>
        <span class="value">{{ $analytics['conversion_rate'] }}%</span>
    </div>
    
    <div class="metric">
        <span class="label">Revenue:</span>
        <span class="value">{{ money($analytics['revenue']['revenue']) }}</span>
    </div>
    
    <div class="metric">
        <span class="label">Stock Turnover:</span>
        <span class="value">{{ $analytics['stock_turnover']['turnover_rate'] }}%</span>
    </div>
    
    <div class="metric">
        <span class="label">Return Rate:</span>
        <span class="value">{{ $analytics['return_rate']['return_rate'] }}%</span>
    </div>
    
    <div class="metric">
        <span class="label">Popularity Rank:</span>
        <span class="value">#{{ $analytics['popularity_rank'] }}</span>
    </div>
</div>
```

## Artisan Commands

### Calculate Analytics

```bash
# Calculate analytics for yesterday
php artisan variants:calculate-analytics

# Calculate for specific date
php artisan variants:calculate-analytics --date=2025-12-25

# Calculate for all variants
php artisan variants:calculate-analytics --all

# Calculate weekly analytics
php artisan variants:calculate-analytics --period=weekly
```

### Cron Setup

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Calculate daily analytics at 2 AM
    $schedule->command('variants:calculate-analytics')
        ->dailyAt('02:00');
    
    // Update popularity rankings daily
    $schedule->command('variants:calculate-analytics --all')
        ->dailyAt('03:00');
}
```

## Best Practices

1. **Track views** on variant pages
2. **Calculate analytics daily** via cron
3. **Update popularity rankings** regularly
4. **Monitor return rates** for quality issues
5. **Track discount impact** for pricing strategy
6. **Use popularity ranking** for recommendations
7. **Store aggregated data** for performance
8. **Filter by date range** for reporting
9. **Compare variants** using analytics
10. **Use conversion rate** for optimization

## Notes

- **Views**: Tracked per session, user, and channel
- **Conversion rate**: Views to orders percentage
- **Revenue**: Includes discounted revenue
- **Stock turnover**: Measures inventory velocity
- **Return rate**: Quality indicator
- **Discount impact**: Estimates revenue impact
- **Popularity score**: Composite metric for ranking
- **Popularity rank**: Relative ranking among variants


