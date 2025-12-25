# Checkout & Order Locking System

## Overview

This system implements a comprehensive checkout and order locking mechanism to ensure:
- **Consistency**: No price drift during checkout
- **No Overselling**: Atomic inventory reservations
- **No Price Drift**: Frozen prices, discounts, tax, and currency rates
- **Deterministic Failure Handling**: Complete rollback on any failure

## Architecture

### 1. Checkout Phases (State Machine)

The checkout process follows a strict state machine with 7 phases:

1. **Cart Validation** (`cart_validation`)
   - Validates cart exists and has items
   - Validates addresses are set
   - Checks stock availability (without reserving)

2. **Inventory Reservation** (`inventory_reservation`)
   - Atomic reservation per variant
   - Links reservations to checkout lock
   - Prevents overselling

3. **Price Lock** (`price_lock`)
   - Creates price snapshots for cart and lines
   - Freezes prices, discounts, tax, currency rates
   - Stores promotion details

4. **Payment Authorization** (`payment_authorization`)
   - Authorizes payment with gateway
   - Stores authorization details

5. **Order Creation** (`order_creation`)
   - Creates order from cart
   - Applies price snapshots to order
   - Ensures prices match locked snapshots

6. **Payment Capture** (`payment_capture`)
   - Captures authorized payment
   - Links payment to order

7. **Stock Commit** (`stock_commit`)
   - Confirms reservations (converts to sale)
   - Decrements inventory
   - Releases reservations

### 2. Order Locking

Once checkout starts:

- **Cart becomes read-only** (protected by middleware)
- **Prices are frozen** (stored in `price_snapshots`)
- **Discounts are frozen** (stored in snapshot)
- **Currency is frozen** (exchange rate locked)
- **Tax rates are frozen** (tax breakdown stored)

All snapshots are stored in the `price_snapshots` table with:
- Cart-level totals
- Line-level prices
- Discount breakdown
- Tax breakdown
- Currency and exchange rate
- Promotion details

### 3. Inventory Locking

- **Atomic reservation per variant**: Each variant gets its own reservation
- **Reservation TTL**: Default 15 minutes, configurable
- **Rollback on failure**: All reservations released if checkout fails
- **Partial fulfillment support**: Multiple warehouses can fulfill one order
- **Warehouse selection locked**: Once reserved, warehouse cannot change
- **Prevent overselling**: Reservations reduce available stock immediately

### 4. Failure Handling

If any phase fails, the system:

1. **Releases stock reservations** (rollback inventory)
2. **Releases price lock** (cart becomes editable again)
3. **Restores cart** (cart remains unchanged)
4. **Invalidates payment authorization** (voids authorization)
5. **Logs failure reason** (stored in `checkout_locks.failure_reason`)

Rollback happens in reverse order of execution to ensure consistency.

### 5. Edge Cases

All edge cases are handled deterministically:

#### Price Changed During Checkout
- Prices are locked at Phase 3 (Price Lock)
- Snapshot prices are used for order creation
- Small differences (< 1 cent) allowed for rounding
- Logged for audit

#### Promotion Expired Mid-Checkout
- Promotion details frozen in snapshot
- Order uses frozen promotion
- No validation after lock

#### Stock Changed Mid-Checkout
- Stock reserved at Phase 2 (Inventory Reservation)
- Reservations validated before order creation
- Expired reservations cause checkout failure

#### Payment Delayed
- Payment authorization stored in lock metadata
- Can be captured later (async payment confirmation)
- Authorization TTL handled by payment gateway

#### Async Payment Confirmation
- Order created with pending payment status
- Payment capture can happen later
- Stock committed only after payment capture

## Database Schema

### `checkout_locks` Table

```sql
- id (bigint)
- cart_id (foreign key)
- session_id (string, indexed)
- user_id (foreign key, nullable)
- state (string, indexed) - pending, validating, reserving, locking_prices, authorizing, creating_order, capturing, committing, completed, failed
- phase (string, nullable) - Current phase name
- failure_reason (json, nullable) - Failure details
- locked_at (timestamp)
- expires_at (timestamp, indexed)
- completed_at (timestamp, nullable)
- failed_at (timestamp, nullable)
- metadata (json, nullable) - Additional checkout metadata
```

### `price_snapshots` Table

```sql
- id (bigint)
- checkout_lock_id (foreign key)
- cart_id (foreign key)
- cart_line_id (foreign key, nullable)
- unit_price (integer)
- sub_total (integer)
- discount_total (integer)
- tax_total (integer)
- total (integer)
- discount_breakdown (json)
- applied_discounts (json)
- tax_breakdown (json)
- tax_rate (decimal)
- currency_code (string, 3)
- compare_currency_code (string, 3)
- exchange_rate (decimal)
- coupon_code (string)
- promotion_details (json)
- snapshot_at (timestamp)
```

## Usage

### Starting Checkout

```php
use App\Services\CheckoutService;
use Lunar\Facades\CartSession;

$cart = CartSession::current();
$checkoutService = app(CheckoutService::class);

// Start checkout (creates lock)
$lock = $checkoutService->startCheckout($cart, ttlMinutes: 15);
```

### Processing Checkout

```php
// Process checkout with payment data
$paymentData = [
    'method' => 'card',
    'token' => $paymentToken,
];

try {
    $order = $checkoutService->processCheckout($lock, $paymentData);
    // Order created successfully
} catch (\Exception $e) {
    // Checkout failed, rollback automatically executed
    // Lock released, cart restored
}
```

### Checking Cart Lock Status

```php
// Check if cart is locked
if ($checkoutService->isCartLocked($cart)) {
    // Cart is being checked out
}

// Get active lock for current session
$lock = $checkoutService->getActiveLock($cart);
```

### Cleanup Expired Locks

Run the cleanup command periodically (via cron):

```bash
php artisan checkout:cleanup-expired-locks
```

Or schedule it in `routes/console.php`:

```php
Schedule::command('checkout:cleanup-expired-locks')
    ->everyFiveMinutes();
```

## Middleware

### ProtectCheckoutCart

Prevents cart modifications during checkout:

```php
// In routes/web.php or route middleware
Route::middleware(['protect.checkout.cart'])->group(function () {
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/{line}', [CartController::class, 'update']);
    Route::delete('/cart/{line}', [CartController::class, 'remove']);
});
```

## State Machine States

- `pending` - Checkout lock created, not started
- `validating` - Validating cart
- `reserving` - Reserving inventory
- `locking_prices` - Locking prices
- `authorizing` - Authorizing payment
- `creating_order` - Creating order
- `capturing` - Capturing payment
- `committing` - Committing stock
- `completed` - Checkout completed successfully
- `failed` - Checkout failed, rollback executed

## Best Practices

1. **Always use transactions**: All checkout operations are wrapped in transactions
2. **Handle exceptions**: Catch exceptions and let rollback execute
3. **Monitor locks**: Cleanup expired locks regularly
4. **Log everything**: All phases and failures are logged
5. **Test edge cases**: Test price changes, stock changes, payment failures
6. **Set appropriate TTL**: Default 15 minutes, adjust based on checkout flow
7. **Protect cart routes**: Use middleware to prevent modifications during checkout

## Integration Points

### Payment Gateway

Integrate payment authorization and capture in:
- `CheckoutStateMachine::authorizePayment()`
- `CheckoutStateMachine::capturePayment()`
- `CheckoutStateMachine::rollbackPaymentAuthorization()`

### Discount System

Discounts are automatically captured in price snapshots. Ensure discounts are calculated before Phase 3 (Price Lock).

### Inventory System

Stock reservations use `StockService`:
- `reserveStock()` - Creates reservation
- `releaseReservation()` - Releases reservation
- `confirmReservation()` - Confirms reservation (converts to sale)

## Monitoring

Monitor checkout health:

```php
// Count active checkouts
$activeCount = CheckoutLock::active()->count();

// Count expired locks
$expiredCount = CheckoutLock::expired()->count();

// Count failed checkouts
$failedCount = CheckoutLock::where('state', CheckoutLock::STATE_FAILED)
    ->where('failed_at', '>', now()->subHour())
    ->count();
```

## Troubleshooting

### Checkout Stuck in State

If checkout is stuck, manually release:

```php
$lock = CheckoutLock::find($lockId);
$checkoutService->releaseCheckout($lock);
```

### Price Mismatch

Check price snapshots:

```php
$snapshot = PriceSnapshot::where('checkout_lock_id', $lockId)
    ->whereNull('cart_line_id')
    ->first();

// Compare with order
$order = Order::find($orderId);
// snapshot->total should match order->total
```

### Stock Not Released

Manually release reservations:

```php
$reservations = StockReservation::where('reference_type', CheckoutLock::class)
    ->where('reference_id', $lockId)
    ->where('is_released', false)
    ->get();

foreach ($reservations as $reservation) {
    $stockService->releaseReservation($reservation);
}
```

