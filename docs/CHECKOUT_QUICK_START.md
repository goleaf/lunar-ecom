# Checkout & Order Locking - Quick Start Guide

## 🚀 5-Minute Setup

### Step 1: Run Migrations
```bash
php artisan migrate
```

### Step 2: Configure Environment
Add to `.env`:
```env
CHECKOUT_TTL_MINUTES=15
CHECKOUT_ENABLE_CART_PROTECTION=true
CHECKOUT_LOGGING_ENABLED=true
```

### Step 3: Test
```bash
# Check health
curl http://localhost/health/checkout

# Monitor checkouts
php artisan checkout:monitor --hours=24
```

**That's it!** The system is ready to use.

## 📝 Basic Usage

### Start Checkout
```php
use App\Services\CheckoutService;
use Lunar\Facades\CartSession;

$cart = CartSession::current();
$checkoutService = app(CheckoutService::class);

$lock = $checkoutService->startCheckout($cart);
```

### Process Checkout
```php
try {
    $order = $checkoutService->processCheckout($lock, [
        'method' => 'card',
        'token' => $paymentToken,
    ]);
    
    // Success!
} catch (\App\Exceptions\CheckoutException $e) {
    // Automatic rollback executed
    // Handle error
}
```

### Check Status
```php
use App\Helpers\CheckoutHelper;

if (CheckoutHelper::isCartLocked()) {
    $status = CheckoutHelper::getStatus();
    echo "State: " . $status['state_name'];
}
```

## 🎯 Key Features

✅ **7-Phase State Machine** - Cart validation → Stock commit  
✅ **Price Locking** - No price drift during checkout  
✅ **Inventory Reservations** - Atomic, prevents overselling  
✅ **Automatic Rollback** - On any failure  
✅ **Cart Protection** - Prevents modifications during checkout  
✅ **Edge Case Handling** - Price/stock/promotion changes  

## 📚 Documentation

- **Complete Guide**: `CHECKOUT_ORDER_LOCKING.md`
- **Integration**: `CHECKOUT_INTEGRATION_GUIDE.md`
- **Production**: `CHECKOUT_PRODUCTION_READY.md`
- **System Complete**: `CHECKOUT_SYSTEM_COMPLETE.md`

## 🔗 Quick Links

- **API Status**: `GET /checkout/status`
- **Health Check**: `GET /health/checkout`
- **Admin**: `GET /admin/checkout-locks`
- **Monitor**: `php artisan checkout:monitor`

## ⚡ Next Steps

1. Integrate payment gateway (update `CheckoutStateMachine`)
2. Set up event listeners (emails, analytics)
3. Configure monitoring alerts
4. Test checkout flow

**Ready to go!** 🎉


