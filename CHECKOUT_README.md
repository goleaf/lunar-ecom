# Checkout & Order Locking System

## Overview

A comprehensive checkout and order locking system that ensures:
- ✅ **Consistency** - No price drift during checkout
- ✅ **No Overselling** - Atomic inventory reservations
- ✅ **No Price Drift** - Frozen prices, discounts, tax, currency
- ✅ **Deterministic Failure Handling** - Complete rollback on any failure

## Quick Start

```bash
# 1. Run migrations
php artisan migrate

# 2. Configure (optional - defaults work)
# Add to .env: CHECKOUT_TTL_MINUTES=15

# 3. Test
php artisan checkout:monitor --hours=24
```

## Documentation

- **[Quick Start](CHECKOUT_QUICK_START.md)** - Get started in 5 minutes
- **[Complete Guide](CHECKOUT_ORDER_LOCKING.md)** - Full system documentation
- **[Integration Guide](CHECKOUT_INTEGRATION_GUIDE.md)** - Integration examples
- **[Production Guide](CHECKOUT_PRODUCTION_READY.md)** - Production deployment
- **[System Complete](CHECKOUT_SYSTEM_COMPLETE.md)** - Complete feature list

## Features

### Checkout Phases
1. Cart Validation
2. Inventory Reservation (atomic)
3. Price Lock (snapshots)
4. Payment Authorization
5. Order Creation
6. Payment Capture
7. Stock Commit

### Order Locking
- Cart becomes read-only
- Prices frozen in snapshots
- Discounts frozen
- Currency rates frozen
- Tax rates frozen

### Inventory Locking
- Atomic reservations per variant
- TTL support (default 15 min)
- Rollback on failure
- Prevents overselling

### Failure Handling
- Automatic rollback
- Releases stock
- Releases price lock
- Restores cart
- Invalidates payment

## API Endpoints

```
GET  /checkout                    - Start checkout
POST /checkout                    - Process checkout
GET  /checkout/status             - Get status
POST /checkout/cancel             - Cancel checkout
GET  /health/checkout             - Health check
GET  /admin/checkout-locks        - Admin interface
```

## Usage

```php
use App\Services\CheckoutService;
use Lunar\Facades\CartSession;

$cart = CartSession::current();
$service = app(CheckoutService::class);

// Start checkout
$lock = $service->startCheckout($cart);

// Process checkout
try {
    $order = $service->processCheckout($lock, $paymentData);
} catch (\App\Exceptions\CheckoutException $e) {
    // Automatic rollback executed
}
```

## Monitoring

```bash
# View statistics
php artisan checkout:monitor --hours=24

# Cleanup expired locks
php artisan checkout:cleanup-expired-locks

# Health check
curl http://your-domain/health/checkout
```

## Status

✅ **Production Ready** - All components implemented and tested

See [CHECKOUT_SYSTEM_COMPLETE.md](CHECKOUT_SYSTEM_COMPLETE.md) for complete feature list.

