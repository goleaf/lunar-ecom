# 📦 Order Status Tracking System

Complete guide for managing orders and tracking statuses with Lunar e-commerce.

## Overview

The order status tracking system provides:
- ✅ Status definitions (pending, processing, shipped, completed, cancelled)
- ✅ Complete status change history
- ✅ Email notifications for status updates
- ✅ Programmatic order creation and status updates
- ✅ Order history tracking
- ✅ Filter orders by status

## Status Definitions

The system includes the following order statuses:

| Status | Label | Color | Description |
|--------|-------|-------|-------------|
| `pending` | Pending | #f59e0b | Order is pending and awaiting processing |
| `processing` | Processing | #3b82f6 | Order is being processed |
| `shipped` | Shipped | #10b981 | Order has been shipped |
| `completed` | Completed | #059669 | Order is completed |
| `cancelled` | Cancelled | #ef4444 | Order has been cancelled |

## Quick Start

### 1. Run Migration

First, run the migration to create the status history table:

```bash
php artisan migrate
```

### 2. Create Orders Programmatically

```php
use App\Lunar\Orders\OrderHelper;
use Lunar\Facades\CartSession;

// Create order from cart
$order = OrderHelper::createFromCart();

// Or create order directly (not recommended)
$order = OrderHelper::create([
    'channel_id' => 1,
    'status' => 'pending',
    'currency_code' => 'USD',
    'sub_total' => 10000, // in cents
    'tax_total' => 2000,
    'total' => 12000,
]);
```

### 3. Update Order Status

```php
use App\Lunar\Orders\OrderHelper;
use App\Services\OrderStatusService;

// Using OrderHelper (recommended)
$order = OrderHelper::find(1);
$order = OrderHelper::updateStatus($order, 'processing', 'Order is being prepared');

// Using OrderStatusService directly
$service = app(OrderStatusService::class);
$order = $service->updateStatus(
    $order,
    'shipped',
    'Order shipped via FedEx',
    ['tracking_number' => '123456789', 'carrier' => 'FedEx']
);
```

### 4. Get Order History

```php
use App\Lunar\Orders\OrderHelper;

$order = OrderHelper::find(1);

// Get complete order history
$history = OrderHelper::getOrderHistory($order);

// Get status history only
$statusHistory = OrderHelper::getStatusHistory($order);
```

### 5. Filter Orders by Status

```php
use App\Lunar\Orders\OrderHelper;

// Get all pending orders
$pendingOrders = OrderHelper::getOrdersByStatus('pending');

// Get latest 10 processing orders
$processingOrders = OrderHelper::getOrdersByStatus('processing', 10);
```

## API Endpoints

### Admin Endpoints

All admin endpoints require authentication.

#### Get Available Statuses
```
GET /admin/orders/statuses
```

Response:
```json
{
  "success": true,
  "statuses": {
    "pending": {
      "label": "Pending",
      "color": "#f59e0b",
      ...
    }
  }
}
```

#### Update Order Status
```
POST /admin/orders/{order}/status
Content-Type: application/json

{
  "status": "processing",
  "notes": "Order is being prepared",
  "meta": {
    "tracking_number": "123456789"
  }
}
```

#### Get Orders by Status
```
GET /admin/orders/status/{status}?limit=10
```

#### Get Order Status History
```
GET /admin/orders/{order}/status-history
```

#### Get Complete Order History
```
GET /admin/orders/{order}/history
```

## Service Layer

### OrderStatusService

The `OrderStatusService` provides core functionality for managing order statuses:

```php
use App\Services\OrderStatusService;

$service = app(OrderStatusService::class);

// Update status
$order = $service->updateStatus($order, 'shipped', 'Shipped via FedEx');

// Get status history
$history = $service->getStatusHistory($order);

// Get orders by status
$orders = $service->getOrdersByStatus('pending', 10);

// Get order history
$history = $service->getOrderHistory($order);

// Get status label
$label = $service->getStatusLabel('pending'); // Returns "Pending"

// Get all available statuses
$statuses = $service->getAvailableStatuses();

// Check if transition is valid
$isValid = $service->isValidTransition('pending', 'processing'); // true
```

## Notifications

### Email Notifications

The system automatically sends email notifications when order status changes:

- **OrderStatusUpdatedNotification**: Sent for most status changes
- **OrderShippedNotification**: Specialized notification for shipped orders with tracking info

#### Customizing Notifications

Edit `config/lunar/orders.php` to configure which notifications are sent for each status:

```php
'statuses' => [
    'shipped' => [
        'label' => 'Shipped',
        'notifications' => [
            \App\Notifications\OrderShippedNotification::class,
        ],
    ],
],
```

#### Sending Custom Notifications

```php
use App\Notifications\OrderShippedNotification;

$order = OrderHelper::find(1);
$user = $order->user;

$user->notify(new OrderShippedNotification(
    $order,
    'shipped',
    '123456789', // tracking number
    'FedEx'      // carrier
));
```

## Status History

Every status change is automatically tracked in the `order_status_history` table:

```php
use App\Models\OrderStatusHistory;

// Get all status changes for an order
$history = OrderStatusHistory::forOrder($order->id)
    ->recent()
    ->with('changedBy')
    ->get();

// Get changes for a specific status
$shippedChanges = OrderStatusHistory::forOrder($order->id)
    ->forStatus('shipped')
    ->get();
```

## Status Transitions

The system validates status transitions to prevent invalid changes:

**Valid Transitions:**
- `pending` → `processing`, `cancelled`
- `processing` → `shipped`, `cancelled`
- `shipped` → `completed`
- `completed` → (terminal state)
- `cancelled` → (terminal state)

You can customize transitions in `OrderStatusService::isValidTransition()`.

## Examples

### Example 1: Complete Order Flow

```php
use App\Lunar\Orders\OrderHelper;
use App\Services\OrderStatusService;

// 1. Create order from cart
$order = OrderHelper::createFromCart();

// 2. Update to processing
OrderHelper::updateStatus($order, 'processing', 'Order received');

// 3. Update to shipped with tracking
$service = app(OrderStatusService::class);
$service->updateStatus(
    $order,
    'shipped',
    'Order shipped',
    ['tracking_number' => '123456789', 'carrier' => 'FedEx']
);

// 4. Mark as completed
OrderHelper::updateStatus($order, 'completed', 'Order delivered');
```

### Example 2: Cancel Order

```php
use App\Lunar\Orders\OrderHelper;

$order = OrderHelper::find(1);

OrderHelper::updateStatus(
    $order,
    'cancelled',
    'Cancelled by customer request'
);
```

### Example 3: Get Order Statistics

```php
use App\Lunar\Orders\OrderHelper;

$pendingCount = OrderHelper::getOrdersByStatus('pending')->count();
$processingCount = OrderHelper::getOrdersByStatus('processing')->count();
$shippedCount = OrderHelper::getOrdersByStatus('shipped')->count();
$completedCount = OrderHelper::getOrdersByStatus('completed')->count();
$cancelledCount = OrderHelper::getOrdersByStatus('cancelled')->count();
```

### Example 4: Display Status History in Admin Panel

```php
use App\Lunar\Orders\OrderHelper;

$order = OrderHelper::find(1);
$history = OrderHelper::getStatusHistory($order);

foreach ($history as $change) {
    echo "{$change->status} - {$change->created_at->format('Y-m-d H:i')}";
    if ($change->changedBy) {
        echo " by {$change->changedBy->name}";
    }
    if ($change->notes) {
        echo " - {$change->notes}";
    }
    echo "\n";
}
```

## Configuration

Edit `config/lunar/orders.php` to customize:

- **Status definitions**: Add, remove, or modify statuses
- **Default draft status**: Change the initial status for new orders
- **Notifications**: Configure which notifications are sent for each status
- **Status colors**: Customize colors for admin UI

## Database Schema

### order_status_history Table

```sql
- id (bigint, primary key)
- order_id (foreign key to orders)
- status (string, indexed)
- previous_status (string, nullable, indexed)
- notes (text, nullable)
- changed_by (foreign key to users, nullable)
- meta (json, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

## Best Practices

1. **Always use OrderHelper or OrderStatusService** instead of directly updating the order status
2. **Add notes** when changing status to provide context
3. **Use meta field** for additional data like tracking numbers
4. **Check status transitions** before updating to prevent invalid changes
5. **Monitor status history** to track order lifecycle
6. **Test notifications** to ensure customers receive updates

## Troubleshooting

### Notifications Not Sending

- Check that the order has an associated user or customer
- Verify notification classes exist and are properly configured
- Check queue workers are running if using `ShouldQueue`
- Review logs for notification errors

### Status Not Updating

- Verify the status exists in `config/lunar/orders.php`
- Check if the status transition is valid
- Ensure you're using `OrderStatusService` or `OrderHelper::updateStatus()`

### History Not Recording

- Verify the migration has been run
- Check that the `order_status_history` table exists
- Ensure transactions are not being rolled back

## Support

For more information, see:
- [Lunar PHP Documentation](https://docs.lunarphp.com)
- Order Helper: `app/Lunar/Orders/OrderHelper.php`
- Order Status Service: `app/Services/OrderStatusService.php`

