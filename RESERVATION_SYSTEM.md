# Reservation System

Complete stock reservation system with cart-based reservations, expiration, partial reservations, order confirmation, manual overrides, and race-condition safe locking.

## Overview

The reservation system provides:
- ✅ **Cart-based reservations** - Reserve stock when items added to cart
- ✅ **Reservation expiration** - Automatic expiration and release
- ✅ **Partial reservations** - Reserve available stock when full quantity unavailable
- ✅ **Order-confirmed reservations** - Convert cart reservations to order reservations
- ✅ **Manual reservation override** - Admin-created reservations
- ✅ **Race-condition safe locking** - Distributed locking for concurrent requests

## Reservation Status

Reservations have the following statuses:

- **cart** - Cart-based reservation (expires automatically)
- **order_confirmed** - Order-confirmed reservation (doesn't expire)
- **manual** - Manual reservation override (doesn't expire by default)
- **expired** - Expired reservation (auto-released)
- **released** - Released reservation

## Usage

### Cart-Based Reservations

```php
use App\Services\ReservationService;
use Lunar\Models\Cart;

$service = app(ReservationService::class);

// Create cart reservation
$reservation = $service->createCartReservation(
    variant: $variant,
    quantity: 5,
    cart: $cart,
    warehouseId: $warehouseId, // Optional
    expirationMinutes: 30      // Optional, defaults to 30
);

// Get cart reservations
$reservations = $service->getCartReservations($cart);

// Release cart reservations (e.g., on cart abandonment)
$released = $service->releaseCartReservations($cart);
```

### Partial Reservations

When full quantity is not available, a partial reservation is created:

```php
// Request 10, but only 7 available
$reservation = $service->createCartReservation($variant, 10, $cart);

// Check if partial
if ($reservation->isPartial()) {
    echo "Partial reservation: {$reservation->reserved_quantity} of {$reservation->quantity}";
    echo "Remaining: {$reservation->remaining_quantity}";
}

// Complete partial reservation when stock arrives
$reservation = $service->completePartialReservation($reservation, 3);
```

### Order-Confirmed Reservations

Convert cart reservation to order-confirmed:

```php
// Confirm reservation when order is created
$reservation = $service->confirmReservation($reservation, $order, $userId);

// Get order reservations
$orderReservations = $service->getOrderReservations($order);
```

### Manual Reservation Override

Create manual reservation (admin override):

```php
// Create manual reservation
$reservation = $service->createManualReservation(
    variant: $variant,
    quantity: 10,
    warehouseId: $warehouseId,
    reason: 'Special order for customer',
    userId: auth()->id(),
    metadata: ['customer_id' => 123]
);
```

### Release Reservations

```php
// Release specific reservation
$service->releaseReservation($reservation, 'cancelled');

// Release expired reservations (run via cron)
// php artisan reservations:release-expired
```

### Extend Reservation

```php
// Extend reservation expiration
$reservation = $service->extendReservation($reservation, 15); // Add 15 minutes
```

## Race-Condition Safe Locking

The system uses distributed locking to prevent race conditions:

```php
// Lock is automatically acquired during reservation creation
$reservation = $service->createCartReservation($variant, $quantity, $cart);

// Lock token is stored in reservation
$lockToken = $reservation->lock_token;
$lockExpiresAt = $reservation->lock_expires_at;
```

### Lock Features

- **Distributed locking** - Uses cache/Redis for distributed systems
- **Lock expiration** - Locks expire automatically (default: 30 seconds)
- **Automatic cleanup** - Expired locks are cleaned up automatically

## Reservation Model

### Fields

```php
StockReservation {
    id
    product_variant_id
    warehouse_id
    inventory_level_id
    quantity                  // Requested quantity
    reserved_quantity         // Actually reserved (for partial)
    status                    // cart, order_confirmed, manual, expired, released
    reference_type            // Cart, Order, etc.
    reference_id
    session_id
    user_id
    lock_token                // Race-condition safe lock token
    locked_at
    lock_expires_at
    expires_at                // Reservation expiration
    is_released
    released_at
    confirmed_at              // Order confirmation timestamp
    confirmed_by              // User who confirmed
    override_reason           // Reason for manual override
    metadata                  // JSON metadata
}
```

### Methods

```php
// Check status
$reservation->isExpired();
$reservation->isPartial();
$reservation->isFullyReserved();
$reservation->isLocked();
$reservation->isLockExpired();
$reservation->isConfirmed();
$reservation->isManual();

// Get remaining quantity
$remaining = $reservation->remaining_quantity;
```

### Scopes

```php
// Active reservations
StockReservation::active()->get();

// Expired reservations
StockReservation::expired()->get();

// Cart reservations
StockReservation::cart()->get();

// Order-confirmed reservations
StockReservation::orderConfirmed()->get();

// Manual reservations
StockReservation::manual()->get();

// Partial reservations
StockReservation::partial()->get();
```

## Integration Examples

### Cart Integration

```php
use App\Services\ReservationService;

class CartController extends Controller
{
    public function addToCart(Request $request, ProductVariant $variant)
    {
        $cart = Cart::current();
        $quantity = $request->input('quantity', 1);

        // Create reservation
        $service = app(ReservationService::class);
        
        try {
            $reservation = $service->createCartReservation(
                variant: $variant,
                quantity: $quantity,
                cart: $cart
            );

            // Add to cart
            $cart->lines()->create([
                'purchasable_type' => ProductVariant::class,
                'purchasable_id' => $variant->id,
                'quantity' => $quantity,
                'meta' => ['reservation_id' => $reservation->id],
            ]);

            return response()->json(['success' => true]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

### Order Confirmation

```php
use App\Services\ReservationService;

class OrderController extends Controller
{
    public function createOrder(Cart $cart)
    {
        $service = app(ReservationService::class);
        
        // Get cart reservations
        $reservations = $service->getCartReservations($cart);

        // Create order
        $order = Order::create([...]);

        // Confirm all reservations
        foreach ($reservations as $reservation) {
            $service->confirmReservation($reservation, $order, auth()->id());
        }

        return $order;
    }
}
```

### Cart Abandonment

```php
use App\Services\ReservationService;

class CartObserver
{
    public function deleted(Cart $cart)
    {
        $service = app(ReservationService::class);
        $service->releaseCartReservations($cart);
    }
}
```

### Admin Manual Reservation

```php
use App\Services\ReservationService;

class AdminInventoryController extends Controller
{
    public function createManualReservation(Request $request, ProductVariant $variant)
    {
        $service = app(ReservationService::class);

        $reservation = $service->createManualReservation(
            variant: $variant,
            quantity: $request->input('quantity'),
            warehouseId: $request->input('warehouse_id'),
            reason: $request->input('reason'),
            userId: auth()->id(),
            metadata: $request->input('metadata', [])
        );

        return response()->json($reservation);
    }
}
```

## Reservation Summary

```php
// Get reservation summary for variant
$summary = $service->getReservationSummary($variant, $warehouseId);

/*
Returns:
[
    'total_reserved' => int,
    'cart_reservations' => int,
    'order_confirmed_reservations' => int,
    'manual_reservations' => int,
    'partial_reservations' => int,
    'expiring_soon' => int,
]
*/
```

## Artisan Commands

### Release Expired Reservations

```bash
php artisan reservations:release-expired
php artisan reservations:release-expired --limit=200
```

Run via cron every minute:
```
* * * * * php artisan reservations:release-expired
```

## Configuration

### Default Expiration Times

```php
// In ReservationService
protected int $defaultExpirationMinutes = 30;        // Cart reservation expiration
protected int $defaultLockExpirationSeconds = 30;    // Lock expiration
```

## Best Practices

1. **Always use ReservationService** - Don't create reservations directly
2. **Handle partial reservations** - Check `isPartial()` and complete when stock arrives
3. **Release expired reservations** - Run cron job regularly
4. **Confirm on order creation** - Convert cart reservations to order-confirmed
5. **Release on cart deletion** - Clean up abandoned cart reservations
6. **Use manual reservations sparingly** - Only for special cases
7. **Monitor reservation summary** - Track reservation status

## Race-Condition Safety

The system uses distributed locking to prevent race conditions:

1. **Lock acquisition** - Lock is acquired before inventory update
2. **Lock expiration** - Locks expire automatically (30 seconds)
3. **Lock cleanup** - Expired locks are cleaned up automatically
4. **Database locking** - Uses `lockForUpdate()` for additional safety

## Partial Reservation Flow

1. **Request 10 units** - Cart requests 10 units
2. **Only 7 available** - Warehouse has only 7 units available
3. **Partial reservation created** - Reservation with `quantity=10`, `reserved_quantity=7`
4. **Stock arrives** - 3 more units arrive
5. **Complete reservation** - Call `completePartialReservation()` to reserve remaining 3 units
6. **Fully reserved** - Reservation now has `reserved_quantity=10`

## Expiration Flow

1. **Cart reservation created** - Expires in 30 minutes
2. **User abandons cart** - Reservation remains active
3. **Expiration time reached** - Reservation expires
4. **Cron job runs** - `reservations:release-expired` command runs
5. **Reservation released** - Stock is released back to available inventory


