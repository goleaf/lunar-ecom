# Product Stock Notifications System

## Overview

A comprehensive "Notify Me When Available" feature that allows customers to subscribe to email notifications when out-of-stock products become available again. Includes automated email notifications with direct purchase links.

## Features

### Core Features

1. **Subscription Management**
   - Subscribe to notifications for out-of-stock products
   - Email and optional name/phone collection
   - Unique unsubscribe tokens
   - Prevent duplicate subscriptions
   - Track subscription status (pending, sent, cancelled)

2. **Email Notifications**
   - Automated email when product is back in stock
   - Product name and image
   - Current price
   - Stock quantity available
   - Direct purchase link
   - Unsubscribe link

3. **Notification Preferences**
   - Notify on backorder (optional)
   - Minimum quantity threshold
   - Product-specific or variant-specific notifications

4. **Automated Checking**
   - Scheduled task to check stock levels
   - Manual command for immediate checking
   - Automatic checking when stock is updated

## Models

### StockNotification
- **Location**: `app/Models/StockNotification.php`
- **Table**: `lunar_stock_notifications`
- **Key Fields**:
  - `product_id`, `product_variant_id`, `customer_id`
  - `email`, `name`, `phone`
  - `status`: pending, sent, cancelled
  - `notified_at`: When notification was sent
  - `notification_count`: Number of times notified
  - `notify_on_backorder`: Whether to notify on backorder
  - `min_quantity`: Minimum stock level to trigger notification
  - `token`: Unique unsubscribe token

## Services

### StockNotificationService
- **Location**: `app/Services/StockNotificationService.php`
- **Methods**:
  - `subscribe()`: Create new subscription
  - `unsubscribe()`: Cancel subscription by token
  - `checkAndNotify()`: Check product and send notifications
  - `checkAndNotifyVariant()`: Check variant and send notifications
  - `checkAllProducts()`: Check all products with pending notifications
  - `getSubscription()`: Get subscription status
  - `getSubscriptionsForEmail()`: Get all subscriptions for an email

## Controllers

### Frontend\StockNotificationController
- `subscribe()`: Subscribe to notifications (AJAX)
- `unsubscribe()`: Unsubscribe via token
- `check()`: Check subscription status (AJAX)

## Notifications

### ProductBackInStock
- **Location**: `app/Notifications/ProductBackInStock.php`
- **Features**:
  - Queued email notification
  - Product details and pricing
  - Direct purchase link
  - Unsubscribe link
  - Formatted with product information

## Commands

### CheckStockNotifications
- **Command**: `php artisan stock:check-notifications`
- **Options**:
  - `--product=ID`: Check specific product
  - `--variant=ID`: Check specific variant
- **Usage**: Run manually or via scheduler

## Routes

```php
Route::prefix('stock-notifications')->name('frontend.stock-notifications.')->group(function () {
    Route::post('/products/{product}/subscribe', [StockNotificationController::class, 'subscribe']);
    Route::get('/unsubscribe/{token}', [StockNotificationController::class, 'unsubscribe']);
    Route::get('/products/{product}/check', [StockNotificationController::class, 'check']);
});
```

## Frontend Components

### Notify Me Button
- **Location**: `resources/views/frontend/components/notify-me-button.blade.php`
- **Usage**: `<x-frontend.notify-me-button :product="$product" />`
- **Features**:
  - Only shows when product is out of stock
  - Inline subscription form
  - AJAX submission
  - Success/error feedback
  - Alpine.js powered

### Unsubscribe Page
- **Location**: `resources/views/frontend/stock-notifications/unsubscribed.blade.php`
- **Features**:
  - Success/error messages
  - Clear confirmation
  - Link back to products

## Email Template

The email notification includes:
- Greeting with customer name
- Product name and image
- Current price
- Stock quantity (if available)
- Direct "View Product" button
- Unsubscribe link

## Scheduled Task

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Check stock notifications every hour
    $schedule->command('stock:check-notifications')
        ->hourly()
        ->withoutOverlapping();
}
```

## Usage Examples

### Subscribe to Notifications
```javascript
fetch('/stock-notifications/products/1/subscribe', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
    },
    body: JSON.stringify({
        email: 'customer@example.com',
        name: 'John Doe',
        product_variant_id: 123,
    })
});
```

### Using StockNotificationService
```php
use App\Services\StockNotificationService;

$service = app(StockNotificationService::class);

// Subscribe
$notification = $service->subscribe([
    'product_id' => $product->id,
    'email' => 'customer@example.com',
    'name' => 'John Doe',
    'notify_on_backorder' => true,
    'min_quantity' => 5,
]);

// Check and send notifications
$sent = $service->checkAndNotify($product);

// Unsubscribe
$service->unsubscribe($token);
```

### Manual Command Execution
```bash
# Check all products
php artisan stock:check-notifications

# Check specific product
php artisan stock:check-notifications --product=1

# Check specific variant
php artisan stock:check-notifications --variant=123
```

## Integration Points

### Product Show Page
- Notify Me button appears when product is out of stock
- Replaces or appears alongside "Add to Cart" button
- Inline subscription form

### Stock Updates
- Can be triggered automatically when stock is updated
- Use observers or events to call `checkAndNotify()`

## Best Practices

1. **Email Validation**
   - Validate email format
   - Check for duplicate subscriptions
   - Prevent spam subscriptions

2. **Notification Timing**
   - Check stock levels regularly (hourly recommended)
   - Send notifications immediately when stock is updated
   - Avoid sending duplicate notifications

3. **User Experience**
   - Clear messaging about subscription
   - Easy unsubscribe process
   - Confirmation after subscription

4. **Performance**
   - Queue email notifications
   - Batch check multiple products
   - Index database tables properly

5. **Privacy**
   - Secure unsubscribe tokens
   - Clear privacy policy
   - Allow users to manage subscriptions

## Future Enhancements

1. **SMS Notifications**
   - Send SMS when product is back in stock
   - Integration with SMS providers

2. **Push Notifications**
   - Browser push notifications
   - Mobile app notifications

3. **Notification Preferences**
   - Email frequency settings
   - Product category preferences
   - Price drop notifications

4. **Admin Dashboard**
   - View all subscriptions
   - Manual notification sending
   - Subscription statistics

5. **Analytics**
   - Track notification open rates
   - Conversion tracking
   - Subscription trends


