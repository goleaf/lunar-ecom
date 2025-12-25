# 🚀 Order Status Tracking - Quick Start

## Installation

```bash
php artisan migrate
```

## Basic Usage

### Update Order Status
```php
use App\Lunar\Orders\OrderHelper;

$order = OrderHelper::find(1);
OrderHelper::updateStatus($order, 'processing', 'Order is being prepared');
```

### Get Status History
```php
$history = OrderHelper::getStatusHistory($order);
```

### Filter Orders by Status
```php
$pendingOrders = OrderHelper::getOrdersByStatus('pending');
```

## Available Statuses

- `pending` - Order is pending
- `processing` - Order is being processed
- `shipped` - Order has been shipped
- `completed` - Order is completed
- `cancelled` - Order is cancelled

## API Endpoints

- `POST /admin/orders/{order}/status` - Update order status
- `GET /admin/orders/{order}/status-history` - Get status history
- `GET /admin/orders/status/{status}` - Get orders by status
- `GET /admin/orders/statuses` - Get all available statuses

## Notifications

Email notifications are automatically sent when order status changes:
- Status updates → `OrderStatusUpdatedNotification`
- Shipped orders → `OrderShippedNotification`

See `ORDER_STATUS_TRACKING.md` for complete documentation.

