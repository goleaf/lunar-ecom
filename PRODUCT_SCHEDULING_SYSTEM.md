# Product Scheduling System

## Overview

A comprehensive product scheduling system that automatically publishes/unpublishes products at specific dates and times, with support for flash sales, seasonal products, and time-limited offerings.

## Features

### Core Features

1. **Automatic Publishing/Unpublishing**
   - Schedule products to publish at specific dates/times
   - Schedule products to unpublish automatically
   - Support for status changes

2. **Flash Sales**
   - Time-limited sales with automatic pricing
   - Percentage or fixed price discounts
   - Automatic price restoration after sale ends
   - Visual badges on product pages

3. **Seasonal Products**
   - Schedule products for specific seasons
   - Automatic publish/unpublish cycles
   - Recurring seasonal schedules

4. **Time-Limited Offers**
   - Products with expiration dates
   - Automatic unpublishing when expired
   - Countdown timers

5. **Recurring Schedules**
   - Daily, weekly, monthly, yearly recurrence
   - Automatic schedule regeneration
   - Configurable recurrence patterns

6. **Scheduled Command**
   - Runs every minute via Laravel scheduler
   - Processes due schedules
   - Handles expired schedules
   - Automatic price restoration

## Models

### ProductSchedule
- **Location**: `app/Models/ProductSchedule.php`
- **Table**: `lunar_product_schedules`
- **Key Fields**:
  - `product_id`: Associated product
  - `type`: Schedule type (publish, unpublish, flash_sale, seasonal, time_limited)
  - `scheduled_at`: When to execute
  - `expires_at`: When schedule expires
  - `target_status`: Status to change to
  - `is_active`: Active status
  - `sale_price`: Flash sale price
  - `sale_percentage`: Flash sale percentage
  - `restore_original_price`: Restore price after sale
  - `is_recurring`: Recurring schedule flag
  - `recurrence_pattern`: Recurrence pattern (daily, weekly, monthly, yearly)
  - `recurrence_config`: Recurrence configuration
  - `send_notification`: Send notification flag
  - `executed_at`: Execution timestamp
  - `execution_success`: Execution success status
  - `execution_error`: Error message if failed

## Services

### ProductSchedulingService
- **Location**: `app/Services/ProductSchedulingService.php`
- **Methods**:
  - `createSchedule()`: Create new schedule
  - `executeDueSchedules()`: Execute all due schedules
  - `executeSchedule()`: Execute single schedule
  - `publishProduct()`: Publish a product
  - `unpublishProduct()`: Unpublish a product
  - `startFlashSale()`: Start flash sale
  - `applySalePrice()`: Apply sale price to variant
  - `endFlashSale()`: End flash sale and restore prices
  - `handleExpiredSchedules()`: Handle expired schedules
  - `createNextRecurrence()`: Create next recurrence
  - `calculateNextRecurrence()`: Calculate next date
  - `getUpcomingSchedules()`: Get upcoming schedules
  - `getActiveSchedules()`: Get active schedules

## Commands

### ProcessProductSchedules
- **Location**: `app/Console/Commands/ProcessProductSchedules.php`
- **Signature**: `products:process-schedules`
- **Schedule**: Every minute
- **Function**: Process due schedules and handle expired schedules

## Controllers

### Admin\ProductScheduleController
- **Location**: `app/Http/Controllers/Admin/ProductScheduleController.php`
- **Methods**:
  - `index()`: Display product schedules
  - `store()`: Create new schedule
  - `update()`: Update schedule
  - `destroy()`: Delete schedule
  - `upcoming()`: Get upcoming schedules
  - `activeFlashSales()`: Get active flash sales

## Routes

```php
// Product schedules
Route::prefix('admin/products/{product}/schedules')->name('admin.products.schedules.')->group(function () {
    Route::get('/', [ProductScheduleController::class, 'index'])->name('index');
    Route::post('/', [ProductScheduleController::class, 'store'])->name('store');
    Route::put('/{schedule}', [ProductScheduleController::class, 'update'])->name('update');
    Route::delete('/{schedule}', [ProductScheduleController::class, 'destroy'])->name('destroy');
});

// Global schedules
Route::prefix('admin/schedules')->name('admin.schedules.')->group(function () {
    Route::get('/upcoming', [ProductScheduleController::class, 'upcoming'])->name('upcoming');
    Route::get('/flash-sales', [ProductScheduleController::class, 'activeFlashSales'])->name('flash-sales');
});
```

## Frontend Components

### Flash Sale Badge
- **Location**: `resources/views/storefront/components/flash-sale-badge.blade.php`
- **Usage**: `<x-storefront.flash-sale-badge :product="$product" />`
- **Features**: Shows flash sale badge with countdown

### Admin Schedules Page
- **Location**: `resources/views/admin/products/schedules.blade.php`
- **Features**:
  - Create schedule form
  - Schedule type selection
  - Flash sale configuration
  - Recurring schedule options
  - Schedule list with status
  - Edit/delete functionality

## Schedule Types

### 1. Publish
- Publishes product at scheduled time
- Changes status to published
- Sets published_at timestamp

### 2. Unpublish
- Unpublishes product at scheduled time
- Changes status to draft/archived
- Hides from storefront

### 3. Flash Sale
- Publishes product if not published
- Applies sale pricing to all variants
- Stores original prices for restoration
- Automatically ends at expiration

### 4. Seasonal
- Publishes product for season
- Can be recurring (yearly)
- Automatic unpublish after season

### 5. Time-Limited
- Publishes product with expiration
- Automatically unpublishes when expired
- Shows countdown timer

## Usage Examples

### Create Flash Sale
```php
use App\Services\ProductSchedulingService;

$service = app(ProductSchedulingService::class);

$schedule = $service->createSchedule($product, [
    'type' => 'flash_sale',
    'scheduled_at' => now()->addHours(2),
    'expires_at' => now()->addDays(2),
    'sale_percentage' => 25, // 25% off
    'restore_original_price' => true,
]);
```

### Create Recurring Seasonal Product
```php
$schedule = $service->createSchedule($product, [
    'type' => 'seasonal',
    'scheduled_at' => now()->startOfYear()->addMonths(11), // December
    'expires_at' => now()->startOfYear()->addMonths(12)->subDay(), // End of December
    'is_recurring' => true,
    'recurrence_pattern' => 'yearly',
]);
```

### Create Time-Limited Offer
```php
$schedule = $service->createSchedule($product, [
    'type' => 'time_limited',
    'scheduled_at' => now()->addDays(1),
    'expires_at' => now()->addDays(7),
    'target_status' => 'published',
]);
```

## Automatic Processing

The system automatically processes schedules via Laravel's task scheduler:

```php
// In routes/console.php
Schedule::command('products:process-schedules')->everyMinute();
```

This command:
1. Finds all due schedules (scheduled_at <= now, not expired, not executed)
2. Executes each schedule
3. Handles expired flash sales and time-limited offers
4. Creates next recurrence for recurring schedules
5. Logs errors for failed executions

## Flash Sale Price Restoration

When a flash sale expires:
1. System finds all variants with sale metadata
2. Restores original prices from custom_meta
3. Removes sale metadata
4. Marks schedule as executed

## Best Practices

1. **Schedule Timing**
   - Schedule at least 1 minute in the future
   - Consider timezone differences
   - Test with small time windows first

2. **Flash Sales**
   - Always set expiration date
   - Enable price restoration
   - Monitor stock levels
   - Send notifications to customers

3. **Recurring Schedules**
   - Use for seasonal products
   - Test recurrence calculation
   - Monitor execution logs

4. **Error Handling**
   - Check execution_success flag
   - Review execution_error messages
   - Set up monitoring/alerts

5. **Performance**
   - Index scheduled_at and expires_at
   - Use queue for large operations
   - Limit concurrent executions

## Future Enhancements

1. **Notifications**
   - Email customers about flash sales
   - Push notifications
   - SMS alerts

2. **Analytics**
   - Track schedule performance
   - Conversion rates for flash sales
   - Revenue from scheduled products

3. **Advanced Recurrence**
   - Custom recurrence patterns
   - Day of week/month selection
   - Holiday-specific schedules

4. **Bulk Scheduling**
   - Schedule multiple products
   - Category-based scheduling
   - Collection-based scheduling

5. **Visual Countdown**
   - Real-time countdown timers
   - Progress bars
   - Urgency indicators

