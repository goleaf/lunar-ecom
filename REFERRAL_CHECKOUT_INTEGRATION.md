# Referral Checkout Integration

## Overview

This document describes how referral discounts are integrated into the checkout process, including eligibility detection, rule evaluation, discount application, and audit trail creation.

## Checkout Stages

The referral system processes discounts at different checkout stages:

1. **Cart Stage** (`cart`): When user adds items to cart
2. **Checkout Stage** (`checkout`): When user proceeds to checkout
3. **Payment Stage** (`payment`): After order is paid

## User Eligibility Detection

Before applying discounts, the system checks:

### 1. User Block Status
- Checks `ReferralUserOverride` for `block_referrals = true`
- Blocks user from receiving referral discounts if set

### 2. Email Verification
- Checks fraud policy requirements
- May require verified email for certain programs

### 3. Active Attribution
- User must have a confirmed `ReferralAttribution`
- Attribution must be within validity period

### 4. Program Eligibility
- Program must be active
- Program must be within start/end dates
- User must be in eligible audience (all/users/groups)

## Rule Evaluation

Rules are evaluated based on checkout stage:

### Cart/Checkout Stage
- **TRIGGER_SIGNUP**: Signup rewards (if user just registered)
- **TRIGGER_FIRST_ORDER_PAID**: First order discounts (can apply before payment)

### Payment Stage
- **TRIGGER_FIRST_ORDER_PAID**: First order rewards (after payment)
- **TRIGGER_NTH_ORDER_PAID**: Nth order rewards (after payment)

### Rule Eligibility Checks

For each rule, the system checks:

1. **Min Order Total**: Cart/order subtotal must meet minimum
2. **Product Eligibility**: 
   - Eligible products (if specified)
   - Eligible categories (if specified)
   - Eligible collections (if specified)
3. **Redemption Limits**:
   - Max total redemptions
   - Max per referrer
   - Max per referee
4. **Validation Window**: Referee must order within X days of attribution
5. **User/Group Overrides**: Check for block or override settings
6. **Cooldown**: Check if cooldown period has passed

## Discount Application

### 1. Get Reward Value

Reward value is determined with override priority:
1. User override (if exists)
2. Group override (if exists)
3. Rule default value

### 2. Create Discount

Creates a Lunar `Discount` model with:
- Unique handle: `referral-{rule_id}-{user_id}`
- Type: Percentage or Fixed (based on reward type)
- Value: Calculated reward value
- Validity: Based on `coupon_validity_days`
- Min basket: From rule `min_order_total`

### 3. Apply Stacking Logic

Uses `ReferralDiscountStackingService` to:
- Apply exclusive mode (remove other discounts)
- Apply best-of mode (choose largest discount)
- Apply stackable mode (stack with caps)

### 4. Calculate Discount Amount

- **Percentage**: `(subtotal * value) / 100`
- **Fixed**: `min(value, subtotal)` (can't exceed subtotal)

## Audit Trail

### Discount Application Record

Every discount application is saved to `referral_discount_applications`:

```php
[
    'order_id' => 123,
    'cart_id' => null,
    'user_id' => 456,
    'referral_attribution_id' => 789,
    'referral_program_id' => 1,
    'applied_rule_ids' => [1, 2],
    'applied_discounts' => [
        [
            'rule_id' => 1,
            'discount_id' => 10,
            'discount_code' => 'referral-1-456',
            'discount_amount' => 15.00,
            'reward_type' => 'percentage_discount',
            'reward_value' => 10.00,
            'stacking_mode' => 'exclusive',
        ],
    ],
    'total_discount_amount' => 15.00,
    'stage' => 'checkout',
    'audit_snapshot' => [...],
]
```

### Audit Snapshot

Complete snapshot of discount application:

```php
[
    'timestamp' => '2025-01-15T10:30:00Z',
    'user_id' => 456,
    'user_email' => 'user@example.com',
    'attribution_id' => 789,
    'referrer_id' => 111,
    'program_id' => 1,
    'rule_ids' => [1, 2],
    'applied_discounts' => [...],
    'cart_or_order_id' => 123,
    'cart_or_order_type' => 'Lunar\Models\Order',
    'subtotal' => 150.00,
    'total_discount_amount' => 15.00,
]
```

### Order Metadata

Discount application is also saved to order `meta`:

```php
$order->meta['referral_discounts'] = [
    'applied_at' => '2025-01-15T10:30:00Z',
    'rules' => [1, 2],
    'discounts' => [...],
    'attribution_id' => 789,
    'audit_snapshot' => [...],
];
```

## Event Listeners

### CartRepricingEvent

**Listener**: `ApplyReferralDiscountsAtCheckout@handleCartRepricing`

**When**: Cart is repriced (quantity changed, variant changed, etc.)

**Action**: 
- Process referral discounts at checkout stage
- Save discount application record

### OrderStatusChanged

**Listener**: `ApplyReferralDiscountsAtCheckout@handleOrderPaid`

**When**: Order status changes to paid

**Action**:
- Process referral discounts at payment stage
- Save discount application to order metadata
- Save discount application record

## Middleware

### ApplyReferralDiscountsMiddleware

**Purpose**: Apply referral discounts on checkout pages

**When**: User visits checkout pages

**Action**:
- Get user's cart
- Process referral discounts
- Store in session for display

## Usage Examples

### Manual Application

```php
use App\Services\ReferralCheckoutService;

$checkoutService = app(ReferralCheckoutService::class);

// Process discounts for cart
$result = $checkoutService->processReferralDiscounts($cart, $user, 'checkout');

if ($result['applied']) {
    // Discounts applied
    foreach ($result['discounts'] as $discount) {
        echo "Applied: {$discount['discount_amount']} from rule {$discount['rule_id']}";
    }
    
    // Save application record
    $checkoutService->saveDiscountApplicationRecord($cart, $user, $result, 'checkout');
}
```

### Check Order Discounts

```php
// Get discount applications for order
$applications = ReferralDiscountApplication::where('order_id', $order->id)->get();

foreach ($applications as $application) {
    echo "Stage: {$application->stage}";
    echo "Total Discount: {$application->total_discount_amount}";
    echo "Rules: " . implode(', ', $application->applied_rule_ids);
}
```

### View Audit Trail

```php
$application = ReferralDiscountApplication::find($id);
$snapshot = $application->audit_snapshot;

echo "Applied at: {$snapshot['timestamp']}";
echo "User: {$snapshot['user_email']}";
echo "Subtotal: {$snapshot['subtotal']}";
echo "Discount: {$snapshot['total_discount_amount']}";
```

## Integration Points

### 1. Cart Calculation

Referral discounts are applied during cart calculation via:
- `CartRepricingEvent` listener
- Cart middleware
- Manual service calls

### 2. Order Creation

Referral discounts are saved when order is created:
- Order metadata
- Discount application record
- Audit snapshot

### 3. Payment Processing

After payment capture:
- Process payment-stage rules
- Issue referrer rewards
- Update discount application records

## Best Practices

1. **Eligibility Checks**: Always check user eligibility before applying discounts
2. **Rule Priority**: Process rules by priority (highest first)
3. **Stacking Logic**: Respect stacking modes and caps
4. **Audit Trail**: Always save audit snapshots for compliance
5. **Error Handling**: Handle cases where discounts can't be applied gracefully
6. **Performance**: Cache eligibility checks where possible
7. **Validation**: Validate discount amounts don't exceed subtotal

## Troubleshooting

### Discounts Not Applying

1. Check user eligibility (not blocked, has attribution)
2. Check program is active and within dates
3. Check rule eligibility (min order, products, limits)
4. Check validation window hasn't expired
5. Check redemption limits haven't been reached

### Discounts Applied Twice

1. Check event listeners aren't registered multiple times
2. Check middleware isn't applied multiple times
3. Check discount application records for duplicates

### Stacking Issues

1. Check stacking mode configuration
2. Check max discount caps
3. Check discount priority ordering
4. Review audit snapshot for stacking decisions


