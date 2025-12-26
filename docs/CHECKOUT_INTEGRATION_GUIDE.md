# Checkout & Order Locking - Integration Guide

## 🔌 Integration Points

### Events

The checkout system fires three main events that you can listen to:

#### 1. CheckoutStarted
Fired when a checkout session begins.

```php
use App\Events\CheckoutStarted;

Event::listen(CheckoutStarted::class, function (CheckoutStarted $event) {
    $lock = $event->lock;
    
    // Example: Track checkout initiation
    Analytics::track('checkout_started', [
        'cart_id' => $lock->cart_id,
        'user_id' => $lock->user_id,
    ]);
});
```

#### 2. CheckoutCompleted
Fired when checkout completes successfully.

```php
use App\Events\CheckoutCompleted;

Event::listen(CheckoutCompleted::class, function (CheckoutCompleted $event) {
    $lock = $event->lock;
    $order = $event->order;
    
    // Example: Send order confirmation email
    Mail::to($order->user->email)->send(new OrderConfirmation($order));
    
    // Example: Update analytics
    Analytics::track('order_placed', [
        'order_id' => $order->id,
        'total' => $order->total,
    ]);
});
```

#### 3. CheckoutFailed
Fired when checkout fails.

```php
use App\Events\CheckoutFailed;

Event::listen(CheckoutFailed::class, function (CheckoutFailed $event) {
    $lock = $event->lock;
    $exception = $event->exception;
    
    // Example: Send failure notification
    Notification::route('slack', config('services.slack.webhook'))
        ->notify(new CheckoutFailedNotification($lock, $exception));
    
    // Example: Track failure
    Analytics::track('checkout_failed', [
        'cart_id' => $lock->cart_id,
        'phase' => $lock->phase,
        'error' => $exception->getMessage(),
    ]);
});
```

### Registering Event Listeners

In `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    CheckoutStarted::class => [
        TrackCheckoutStart::class,
        // Add your listeners here
    ],
    CheckoutCompleted::class => [
        SendOrderConfirmation::class,
        UpdateInventory::class,
        // Add your listeners here
    ],
    CheckoutFailed::class => [
        NotifyAdmin::class,
        // Add your listeners here
    ],
];
```

## 💳 Payment Gateway Integration

### Step 1: Update Authorization Method

In `app/Services/CheckoutStateMachine.php`, update `authorizePayment()`:

```php
protected function authorizePayment(CheckoutLock $lock, array $paymentData): array
{
    $lock->updateState(CheckoutLock::STATE_AUTHORIZING, self::PHASE_PAYMENT_AUTHORIZATION);

    // Integrate with your payment gateway
    $gateway = app(PaymentGateway::class);
    
    $authorization = $gateway->authorize([
        'amount' => $lock->cart->total->value,
        'currency' => $lock->cart->currency->code,
        'token' => $paymentData['token'] ?? null,
        'method' => $paymentData['method'] ?? 'card',
    ]);

    if (!$authorization->successful()) {
        throw new CheckoutException(
            'Payment authorization failed',
            self::PHASE_PAYMENT_AUTHORIZATION,
            ['gateway_error' => $authorization->error()]
        );
    }

    $paymentAuth = [
        'authorization_id' => $authorization->id,
        'status' => 'authorized',
        'amount' => $lock->cart->total->value,
        'currency' => $lock->cart->currency->code,
        'authorized_at' => now()->toIso8601String(),
        'gateway_response' => $authorization->toArray(),
    ];

    // Store in lock metadata
    $metadata = $lock->metadata ?? [];
    $metadata['payment_authorization'] = $paymentAuth;
    $lock->update(['metadata' => $metadata]);

    $this->logger->phaseTransition($lock, self::PHASE_PAYMENT_AUTHORIZATION, [
        'authorization_id' => $paymentAuth['authorization_id'],
    ]);

    return $paymentAuth;
}
```

### Step 2: Update Capture Method

```php
protected function capturePayment(CheckoutLock $lock, Order $order, array $paymentAuth): void
{
    $lock->updateState(CheckoutLock::STATE_CAPTURING, self::PHASE_PAYMENT_CAPTURE);

    $gateway = app(PaymentGateway::class);
    
    $capture = $gateway->capture([
        'authorization_id' => $paymentAuth['authorization_id'],
        'amount' => $order->total,
        'order_id' => $order->id,
    ]);

    if (!$capture->successful()) {
        throw new CheckoutException(
            'Payment capture failed',
            self::PHASE_PAYMENT_CAPTURE,
            ['gateway_error' => $capture->error()]
        );
    }

    $captureData = [
        'capture_id' => $capture->id,
        'authorization_id' => $paymentAuth['authorization_id'],
        'status' => 'captured',
        'amount' => $order->total,
        'captured_at' => now()->toIso8601String(),
        'gateway_response' => $capture->toArray(),
    ];

    // Store in order metadata
    $meta = $order->meta ?? [];
    $meta['payment'] = $captureData;
    $order->update(['meta' => $meta]);

    $this->logger->phaseTransition($lock, self::PHASE_PAYMENT_CAPTURE, [
        'order_id' => $order->id,
        'capture_id' => $captureData['capture_id'],
    ]);
}
```

### Step 3: Update Rollback Method

```php
protected function rollbackPaymentAuthorization(array $data): void
{
    $payment = $data['payment'] ?? null;
    
    if ($payment && isset($payment['authorization_id'])) {
        $gateway = app(PaymentGateway::class);
        
        // Void/cancel authorization
        $gateway->void($payment['authorization_id']);
        
        $this->logger->rollbackStep($data['lock'], 'rollbackPaymentAuthorization', true);
    }
}
```

## 📊 Monitoring Integration

### Health Check Endpoint

The system provides a health check endpoint:

```
GET /health/checkout
```

Response:
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

### Metrics Collection

Example integration with monitoring service:

```php
use App\Events\CheckoutCompleted;
use App\Events\CheckoutFailed;

Event::listen(CheckoutCompleted::class, function (CheckoutCompleted $event) {
    // Send to metrics service
    Metrics::increment('checkout.completed', [
        'currency' => $event->order->currency_code,
    ]);
    
    Metrics::histogram('checkout.duration', 
        $event->lock->completed_at->diffInSeconds($event->lock->locked_at)
    );
});

Event::listen(CheckoutFailed::class, function (CheckoutFailed $event) {
    Metrics::increment('checkout.failed', [
        'phase' => $event->lock->phase,
    ]);
});
```

## 🔔 Notification Integration

### Email Notifications

```php
use App\Events\CheckoutCompleted;
use App\Events\CheckoutFailed;

Event::listen(CheckoutCompleted::class, function (CheckoutCompleted $event) {
    $order = $event->order;
    
    // Send to customer
    Mail::to($order->user->email)->send(new OrderConfirmation($order));
    
    // Send to admin
    Mail::to(config('mail.admin'))->send(new NewOrderNotification($order));
});

Event::listen(CheckoutFailed::class, function (CheckoutFailed $event) {
    // Notify admin of failures
    if (config('checkout.notifications.on_failure')) {
        Mail::to(config('checkout.notifications.email'))
            ->send(new CheckoutFailureNotification($event->lock, $event->exception));
    }
});
```

### Slack Notifications

```php
use App\Events\CheckoutFailed;

Event::listen(CheckoutFailed::class, function (CheckoutFailed $event) {
    if (config('checkout.notifications.on_failure')) {
        Notification::route('slack', config('services.slack.webhook'))
            ->notify(new CheckoutFailedSlackNotification($event->lock));
    }
});
```

## 🔍 Logging Integration

The system uses `CheckoutLogger` for structured logging. Logs are written to the channel specified in `config/checkout.php`.

### Custom Log Channel

In `config/logging.php`:

```php
'channels' => [
    'checkout' => [
        'driver' => 'daily',
        'path' => storage_path('logs/checkout.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],
```

Then in `config/checkout.php`:

```php
'logging' => [
    'channel' => 'checkout',
    // ...
],
```

## 🧪 Testing Integration

### Example Test

```php
use App\Events\CheckoutCompleted;
use App\Models\CheckoutLock;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Event;
use Lunar\Facades\CartSession;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    public function test_checkout_completes_successfully()
    {
        Event::fake();
        
        $cart = CartSession::current();
        $checkoutService = app(CheckoutService::class);
        
        $lock = $checkoutService->startCheckout($cart);
        
        $order = $checkoutService->processCheckout($lock, [
            'method' => 'card',
            'token' => 'test_token',
        ]);
        
        $this->assertNotNull($order);
        $this->assertEquals($lock->cart_id, $cart->id);
        
        Event::assertDispatched(CheckoutCompleted::class);
    }
    
    public function test_checkout_fails_and_rolls_back()
    {
        Event::fake();
        
        // Setup cart with insufficient stock
        // ...
        
        $checkoutService = app(CheckoutService::class);
        $lock = $checkoutService->startCheckout($cart);
        
        $this->expectException(\App\Exceptions\CheckoutException::class);
        
        $checkoutService->processCheckout($lock);
        
        Event::assertDispatched(CheckoutFailed::class);
        
        // Verify rollback
        $this->assertTrue($lock->isFailed());
    }
}
```

## 🔐 Security Considerations

### Rate Limiting

Add to `routes/web.php`:

```php
Route::middleware(['throttle:checkout'])->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'store']);
});
```

In `app/Http/Kernel.php` or `bootstrap/app.php`:

```php
RateLimiter::for('checkout', function (Request $request) {
    return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
});
```

### CSRF Protection

Already handled by Laravel's CSRF middleware for web routes.

## 📱 Frontend Integration

### JavaScript Example

```javascript
// Start checkout
async function startCheckout() {
    const response = await fetch('/checkout', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
    
    const data = await response.json();
    return data.lock_id;
}

// Process checkout
async function processCheckout(lockId, paymentData) {
    const response = await fetch('/checkout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            ...paymentData,
            shipping_address: getShippingAddress(),
            billing_address: getBillingAddress(),
        }),
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message);
    }
    
    return await response.json();
}

// Check status
async function getCheckoutStatus() {
    const response = await fetch('/checkout/status');
    return await response.json();
}
```

## 🎯 Best Practices

1. **Always use transactions** - All checkout operations are wrapped in transactions
2. **Handle exceptions** - Catch `CheckoutException` for proper error handling
3. **Monitor health** - Use health check endpoint in monitoring
4. **Listen to events** - Use events for side effects (emails, analytics, etc.)
5. **Test thoroughly** - Test all phases and failure scenarios
6. **Log everything** - Use structured logging for debugging
7. **Set appropriate TTL** - Adjust based on your checkout flow duration
8. **Protect endpoints** - Use rate limiting and authentication

## 📚 Additional Resources

- Complete Documentation: `CHECKOUT_ORDER_LOCKING.md`
- Implementation Summary: `CHECKOUT_IMPLEMENTATION_SUMMARY.md`
- Production Guide: `CHECKOUT_PRODUCTION_READY.md`


