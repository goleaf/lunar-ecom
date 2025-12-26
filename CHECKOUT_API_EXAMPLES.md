# Checkout & Order Locking - API Examples

## 📡 API Response Examples

### Checkout Status

**Endpoint:** `GET /checkout/status`

**Response:**
```json
{
  "locked": true,
  "can_checkout": false,
  "lock_id": 123,
  "state": "reserving",
  "state_name": "Reserving Stock",
  "phase": "inventory_reservation",
  "expires_at": "2025-01-15T10:45:00Z",
  "can_resume": true
}
```

**When not locked:**
```json
{
  "locked": false,
  "can_checkout": true
}
```

### Start Checkout

**Endpoint:** `GET /checkout`

**Response:** Redirects to checkout page, creates lock

### Process Checkout

**Endpoint:** `POST /checkout`

**Request:**
```json
{
  "shipping_address": {
    "first_name": "John",
    "last_name": "Doe",
    "line_one": "123 Main St",
    "city": "New York",
    "postcode": "10001",
    "country_id": 1
  },
  "billing_address": {
    "first_name": "John",
    "last_name": "Doe",
    "line_one": "123 Main St",
    "city": "New York",
    "postcode": "10001",
    "country_id": 1
  },
  "payment_method": "card",
  "payment_token": "tok_1234567890"
}
```

**Success Response:** Redirects to confirmation page

**Error Response:**
```json
{
  "error": "Checkout failed",
  "message": "Insufficient stock for variant SKU123. Only 5 available.",
  "phase": "inventory_reservation",
  "context": {
    "variant_id": 456,
    "variant_sku": "SKU123",
    "requested": 10,
    "available": 5
  }
}
```

### Cancel Checkout

**Endpoint:** `POST /checkout/cancel`

**Response:**
```json
{
  "success": true,
  "message": "Checkout cancelled successfully"
}
```

### Health Check

**Endpoint:** `GET /health/checkout`

**Response:**
```json
{
  "status": "healthy",
  "checks": {
    "expired_locks": {
      "status": "ok",
      "count": 2,
      "message": "2 expired locks (normal)"
    },
    "stuck_checkouts": {
      "status": "ok",
      "count": 0,
      "message": "No stuck checkouts"
    },
    "database": {
      "status": "ok",
      "message": "Database connection healthy"
    }
  },
  "timestamp": "2025-01-15T10:30:00Z"
}
```

### Admin - List Locks

**Endpoint:** `GET /admin/checkout-locks`

**Query Parameters:**
- `state` - Filter by state
- `date_from` - Filter from date
- `date_to` - Filter to date

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "cart_id": 456,
      "state": "completed",
      "state_name": "Completed",
      "phase": "stock_commit",
      "locked_at": "2025-01-15T10:00:00Z",
      "completed_at": "2025-01-15T10:00:30Z",
      "duration": "30s",
      "is_active": false,
      "is_completed": true
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### Admin - Get Lock Details

**Endpoint:** `GET /admin/checkout-locks/{id}/json`

**Response:**
```json
{
  "id": 123,
  "cart_id": 456,
  "session_id": "abc123",
  "user_id": 789,
  "state": "completed",
  "state_name": "Completed",
  "phase": "stock_commit",
  "locked_at": "2025-01-15T10:00:00Z",
  "expires_at": "2025-01-15T10:15:00Z",
  "completed_at": "2025-01-15T10:00:30Z",
  "failed_at": null,
  "is_active": false,
  "is_completed": true,
  "is_failed": false,
  "is_expired": false,
  "can_resume": false,
  "duration": "30s",
  "failure_reason": null,
  "metadata": {
    "order_id": 999,
    "payment_authorization": {...}
  }
}
```

### Admin - Statistics

**Endpoint:** `GET /admin/checkout-locks/statistics?hours=24`

**Response:**
```json
{
  "active": 5,
  "completed": 150,
  "failed": 10,
  "expired": 2,
  "states": {
    "completed": 150,
    "failed": 10,
    "reserving": 3,
    "authorizing": 2
  },
  "success_rate": 93.75
}
```

## 🔄 State Flow Example

```
1. GET /checkout
   → Creates lock (state: pending)
   → Returns checkout page

2. POST /checkout
   → State: validating → reserving → locking_prices → authorizing → creating_order → capturing → committing → completed
   → Returns order confirmation

OR on failure:
   → State: [current] → failed
   → Rollback executed
   → Returns error
```

## 📝 Error Handling

All errors use `CheckoutException` format:

```json
{
  "error": "Checkout failed",
  "message": "Human-readable error message",
  "phase": "inventory_reservation",
  "context": {
    "variant_id": 123,
    "available": 5,
    "requested": 10
  }
}
```

**HTTP Status Codes:**
- `200` - Success
- `422` - Validation/Checkout error
- `423` - Cart locked
- `429` - Rate limit exceeded
- `503` - System unhealthy

## 🔐 Rate Limiting

Rate limit headers included in responses:

```
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 3
Retry-After: 45
```

## 📊 Monitoring Endpoints

### Command Line
```bash
# Monitor statistics
php artisan checkout:monitor --hours=24

# Run diagnostics
php artisan checkout:diagnostics
php artisan checkout:diagnostics --lock-id=123

# Cleanup expired locks
php artisan checkout:cleanup-expired-locks
```

## 🎯 Integration Examples

See `CHECKOUT_INTEGRATION_GUIDE.md` for:
- Event listener examples
- Payment gateway integration
- Monitoring integration
- Notification setup
- Testing examples


