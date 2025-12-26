# Referral Event-Driven Automation

## Overview

The referral system uses an event-driven architecture for automation, ensuring all referral actions are properly tracked, logged, and processed asynchronously.

## Events

### 1. ReferralClicked

**Fired when**: A referral link is clicked

**Event Class**: `App\Events\ReferralClicked`

**Payload**:
```php
[
    'click' => ReferralClick model
]
```

**Listeners**:
- `HandleReferralClicked`: Logs click and handles attribution

**Usage**:
```php
use App\Events\ReferralClicked;
use App\Models\ReferralClick;

event(new ReferralClicked($click));
```

### 2. UserSignedUp

**Fired when**: A user signs up with referral attribution

**Event Class**: `App\Events\UserSignedUp`

**Payload**:
```php
[
    'user' => User model,
    'referralCode' => string|null,
    'attributionId' => int|null
]
```

**Listeners**:
- `HandleUserSignedUp`: Confirms attribution, performs fraud checks, issues signup rewards

**Usage**:
```php
use App\Events\UserSignedUp;

event(new UserSignedUp($user, $referralCode, $attributionId));
```

### 3. OrderPaid

**Fired when**: An order is paid

**Event Class**: `App\Events\OrderPaid`

**Payload**:
```php
[
    'order' => Order model
]
```

**Listeners**:
- `HandleOrderPaid`: Confirms attribution, issues referrer/referree rewards, logs everything

**Usage**:
```php
use App\Events\OrderPaid;

event(new OrderPaid($order));
```

### 4. OrderRefunded

**Fired when**: An order is refunded

**Event Class**: `App\Events\OrderRefunded`

**Payload**:
```php
[
    'order' => Order model,
    'refundAmount' => float|null,
    'reason' => string|null
]
```

**Listeners**:
- `HandleOrderRefunded`: Reverses all referral rewards for the order

**Usage**:
```php
use App\Events\OrderRefunded;

event(new OrderRefunded($order, $refundAmount, $reason));
```

### 5. ChargebackReceived

**Fired when**: A chargeback is received for an order

**Event Class**: `App\Events\ChargebackReceived`

**Payload**:
```php
[
    'order' => Order model,
    'chargebackId' => string|null,
    'reason' => string|null,
    'amount' => float|null
]
```

**Listeners**:
- `HandleChargebackReceived`: Reverses all referral rewards for the order

**Usage**:
```php
use App\Events\ChargebackReceived;

event(new ChargebackReceived($order, $chargebackId, $reason, $amount));
```

## Event Flow

### Signup Flow

```
User Clicks Referral Link
    ↓
ReferralClicked Event
    ↓
Attribution Created (Pending)
    ↓
User Registers
    ↓
UserSignedUp Event
    ↓
Fraud Checks
    ↓
Attribution Confirmed
    ↓
Signup Rewards Issued
```

### Order Paid Flow

```
Order Status → Paid
    ↓
OrderPaid Event
    ↓
Find Attribution
    ↓
Check if First Order
    ↓
Process Rules:
  - First Order Paid
  - Nth Order Paid
    ↓
Issue Rewards:
  - Referee Coupon/Discount
  - Referrer Store Credit/Coupon
    ↓
Log Everything
```

### Refund/Chargeback Flow

```
Order Refunded/Chargeback
    ↓
OrderRefunded/ChargebackReceived Event
    ↓
Find Reward Issuances
    ↓
Reverse Each Reward:
  - Invalidate Coupons
  - Deduct Store Credit
  - Mark Issuance Reversed
    ↓
Log Reversal
```

## Listeners

### HandleReferralClicked

**Purpose**: Track referral link clicks

**Actions**:
- Log click event
- Attribution handled by ReferralAttributionService

### HandleUserSignedUp

**Purpose**: Process user signup with referral

**Actions**:
1. Find referral attribution
2. Run fraud checks
3. Confirm attribution (if valid)
4. Issue signup rewards
5. Log everything

**Fraud Checks**:
- Self-referral detection
- IP address limits
- Disposable email check
- Card fingerprint check
- Verification requirements

### HandleOrderPaid

**Purpose**: Process referral rewards when order is paid

**Actions**:
1. Find confirmed attribution
2. Check if first order
3. Count paid orders
4. Process applicable rules:
   - `TRIGGER_FIRST_ORDER_PAID`
   - `TRIGGER_NTH_ORDER_PAID`
5. Issue rewards:
   - Referee: Coupon/Discount (if post-purchase)
   - Referrer: Store Credit/Coupon
6. Create audit log

**Reward Types**:
- **Referee**: Coupon, Percentage Discount, Fixed Discount, Free Shipping, Store Credit
- **Referrer**: Coupon, Store Credit, Percentage Discount (Next Order), Fixed Amount

### HandleOrderRefunded

**Purpose**: Reverse referral rewards when order is refunded

**Actions**:
1. Find all reward issuances for order
2. Reverse each issuance:
   - Invalidate coupons
   - Deduct store credit
   - Mark issuance as reversed
3. Log reversal

### HandleChargebackReceived

**Purpose**: Reverse referral rewards when chargeback occurs

**Actions**:
- Same as HandleOrderRefunded
- Additional chargeback-specific logging

## Reward Reversal Service

### ReferralRewardReversalService

**Methods**:

#### `reverseOrderRewards(Order $order, string $reason, string $type)`

Reverses all rewards for an order.

**Parameters**:
- `$order`: The refunded/chargeback order
- `$reason`: Reason for reversal
- `$type`: 'refund' or 'chargeback'

**Returns**:
```php
[
    'success' => bool,
    'reversed_count' => int,
    'errors_count' => int,
    'reversed' => array,
    'errors' => array,
]
```

**Reversal Actions**:

1. **Coupon Invalidation**:
   - Set `end_at` to past date
   - Add reversal metadata
   - Prevents future use

2. **Store Credit Reversal**:
   - Deduct amount from wallet
   - Create debit transaction
   - Log reversal

3. **Discount Reversal**:
   - Already applied, cannot reverse
   - Logged for audit

4. **Free Shipping Reversal**:
   - Already used, cannot reverse
   - Logged for audit

## Integration Points

### Order Observer

The `OrderObserver` fires events when order status changes:

```php
public function updated(Order $order): void
{
    if ($order->wasChanged('status')) {
        // Fire OrderPaid when order becomes paid
        if ($order->isPaid()) {
            event(new OrderPaid($order));
        }

        // Fire OrderRefunded when order is refunded
        if ($order->isRefunded()) {
            event(new OrderRefunded($order, $refundAmount, $reason));
        }
    }
}
```

### TrackReferralLink Middleware

Fires `ReferralClicked` event when referral link is clicked:

```php
$click = ReferralClick::create([...]);
event(new ReferralClicked($click));
```

### ProcessUserRegistration Listener

Fires `UserSignedUp` event after attribution:

```php
event(new UserSignedUp($user, $explicitCode, $attribution->id));
```

## Logging

### Audit Logs

All events create audit log entries:

**Order Paid**:
```php
ReferralAuditLog::create([
    'actor' => 'system',
    'action' => 'order_paid',
    'before' => [...],
    'after' => [...],
    'timestamp' => now(),
]);
```

**Reward Reversal**:
```php
ReferralAuditLog::create([
    'actor' => 'system',
    'action' => 'reward_reversed',
    'before' => [...],
    'after' => [...],
    'timestamp' => now(),
]);
```

### Application Logs

All events are logged using Laravel's logging:

```php
Log::info('Referral reward issued', [
    'rule_id' => $rule->id,
    'order_id' => $order->id,
    'referee_id' => $user->id,
    'referrer_id' => $referrer->id,
]);
```

## Queue Processing

All listeners implement `ShouldQueue` for asynchronous processing:

```php
class HandleOrderPaid implements ShouldQueue
{
    use InteractsWithQueue;
    // ...
}
```

**Benefits**:
- Non-blocking checkout
- Retry on failure
- Better performance
- Scalability

## Error Handling

### Transaction Safety

Critical operations use database transactions:

```php
DB::beginTransaction();
try {
    // Issue rewards
    // Log actions
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    Log::error('Failed to process rewards', [...]);
    throw $e;
}
```

### Retry Logic

Failed jobs are automatically retried by Laravel queue system.

### Error Logging

All errors are logged with context:

```php
Log::error('Failed to reverse referral rewards', [
    'order_id' => $order->id,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

## Best Practices

1. **Always Fire Events**: Use events instead of direct service calls for referral actions
2. **Queue Listeners**: Use `ShouldQueue` for all listeners to avoid blocking
3. **Transaction Safety**: Wrap critical operations in transactions
4. **Comprehensive Logging**: Log all actions for audit trail
5. **Error Handling**: Always handle exceptions gracefully
6. **Idempotency**: Ensure listeners can be safely retried
7. **Event Ordering**: Consider event order when multiple listeners exist

## Testing Events

### Manual Event Firing

```php
use App\Events\OrderPaid;

// Fire event manually
event(new OrderPaid($order));
```

### Testing Listeners

```php
use App\Events\OrderPaid;
use App\Listeners\HandleOrderPaid;

$listener = new HandleOrderPaid($rewardService, $fraudService);
$listener->handle(new OrderPaid($order));
```

## Monitoring

### Event Tracking

Monitor event firing:
- Check Laravel logs for event logs
- Monitor queue jobs for listener execution
- Track audit logs for all actions

### Metrics

Track:
- Events fired per day
- Listener execution time
- Failed jobs count
- Reward issuance rate
- Reversal rate

## Future Enhancements

- Webhook notifications for external systems
- Real-time event streaming
- Event replay capability
- Advanced retry strategies
- Event versioning


