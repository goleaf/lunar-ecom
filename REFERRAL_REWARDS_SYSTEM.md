# Referral Rewards & Discount System

## Overview

This document describes the referral reward and discount system that handles both referee (invited user) and referrer rewards with comprehensive fraud prevention.

## Reward Patterns

### Referee Rewards (Invited Users)

#### 1. Signup Reward
- **Trigger**: User signs up via referral link
- **Example**: 10% off first order
- **Implementation**: Coupon or discount code issued immediately after signup

#### 2. First Paid Order Reward
- **Trigger**: Referee completes first paid order
- **Example**: €5 off orders over €50
- **Implementation**: Discount applied to order or coupon for next order

#### 3. Category-Only Discount
- **Trigger**: Order contains products from eligible categories
- **Example**: 15% off electronics category
- **Implementation**: Discount only applies to specified categories

#### 4. Free Shipping
- **Trigger**: First order or order over threshold
- **Example**: Free shipping for first order
- **Implementation**: Shipping discount applied

### Referrer Rewards

#### 1. Store Credit
- **Trigger**: Referee completes first paid order
- **Example**: €10 store credit after referee's first purchase
- **Implementation**: Credit added to referrer's wallet

#### 2. Coupon for Next Order
- **Trigger**: Referee completes purchase
- **Example**: 20% off next order coupon
- **Implementation**: Coupon code generated and assigned to referrer

#### 3. Tiered Rewards
- **Trigger**: Multiple referrals completed
- **Example**: 
  - 1st referral = €5
  - 5th referral = €10
  - 10th referral = VIP group membership
- **Implementation**: Reward value increases based on referral count

## Fraud Prevention Rules

### Payment Verification
- ✅ **Payment Captured**: Rewards only issued after payment is captured
- ✅ **Order Not Refunded**: Rewards reversed if order refunded within X days
- ✅ **Refund Window**: Configurable days before reward is safe (default: 7-30 days)

### User Verification
- ✅ **Email Verified**: Require email verification before reward
- ✅ **Phone Verified**: Require phone verification before reward
- ✅ **Account Age**: Minimum account age before reward (e.g., 7 days)

### Abuse Prevention
- ✅ **Self-Referral Prevention**: Referrer cannot refer themselves
- ✅ **Same IP Blocking**: Option to block same IP addresses
- ✅ **IP Limits**: Max signups/orders per IP per day
- ✅ **Disposable Email Blocking**: Block temporary email addresses
- ✅ **Card Fingerprint**: Block same card used by referrer
- ✅ **One Referee, One Referrer**: Each referee can only reward one referrer
- ✅ **Cooldown Period**: Days between rewards for same referrer

## Implementation

### Reward Service

The `ReferralRewardService` handles all reward issuance:

```php
use App\Services\ReferralRewardService;

$rewardService = app(ReferralRewardService::class);

// Process reward for trigger event
$rewardService->processReward(
    $referee,
    ReferralRule::TRIGGER_FIRST_ORDER_PAID,
    $order
);
```

### Fraud Service

The `ReferralFraudService` validates all rewards:

```php
use App\Services\ReferralFraudService;

$fraudService = app(ReferralFraudService::class);

if ($fraudService->canIssueReward($rule, $referee, $referrer, $order)) {
    // Issue reward
}
```

### Event Listeners

Rewards are automatically processed via event listeners:

1. **ProcessReferralSignup**: Handles signup rewards
2. **ProcessReferralOrderPayment**: Handles order-based rewards
3. **ProcessUserRegistration**: Creates attribution, triggers signup rewards

## Rule Configuration

### Example: Signup Reward Rule

```php
ReferralRule::create([
    'referral_program_id' => $program->id,
    'trigger_event' => ReferralRule::TRIGGER_SIGNUP,
    'referee_reward_type' => ReferralRule::REWARD_PERCENTAGE_DISCOUNT,
    'referee_reward_value' => 10.00, // 10%
    'referrer_reward_type' => null, // No referrer reward for signup
    'min_order_total' => null, // No minimum
    'coupon_validity_days' => 30,
    'is_active' => true,
]);
```

### Example: First Order Reward Rule

```php
ReferralRule::create([
    'referral_program_id' => $program->id,
    'trigger_event' => ReferralRule::TRIGGER_FIRST_ORDER_PAID,
    'referee_reward_type' => ReferralRule::REWARD_FIXED_DISCOUNT,
    'referee_reward_value' => 5.00, // €5 off
    'referrer_reward_type' => ReferralRule::REWARD_STORE_CREDIT,
    'referrer_reward_value' => 10.00, // €10 credit
    'min_order_total' => 50.00, // Minimum €50 order
    'eligible_category_ids' => [1, 2, 3], // Only certain categories
    'max_redemptions_per_referee' => 1, // One-time only
    'cooldown_days' => 0, // No cooldown
    'validation_window_days' => 30, // Must order within 30 days
    'fraud_policy_id' => $fraudPolicy->id,
    'is_active' => true,
]);
```

### Example: Tiered Rewards Rule

```php
ReferralRule::create([
    'referral_program_id' => $program->id,
    'trigger_event' => ReferralRule::TRIGGER_FIRST_ORDER_PAID,
    'referrer_reward_type' => ReferralRule::REWARD_STORE_CREDIT,
    'referrer_reward_value' => 5.00, // Base value
    'tiered_rewards' => [
        '1' => 5.00,   // 1st referral: €5
        '5' => 10.00,   // 5th referral: €10
        '10' => 25.00,  // 10th referral: €25
    ],
    'is_active' => true,
]);
```

## Reward Types

### Referee Reward Types

- `coupon`: Coupon code (fixed amount or percentage)
- `percentage_discount`: Percentage discount
- `fixed_discount`: Fixed amount discount
- `free_shipping`: Free shipping discount
- `store_credit`: Store credit added to wallet

### Referrer Reward Types

- `coupon`: Coupon code for next order
- `store_credit`: Store credit added to wallet
- `percentage_discount_next_order`: Percentage discount for next order
- `fixed_amount`: Fixed amount store credit

## Reward Issuance Flow

1. **Event Triggered**: User signup or order payment
2. **Attribution Check**: Verify user has confirmed attribution
3. **Rule Matching**: Find applicable rules for trigger event
4. **Eligibility Check**: Verify rule conditions (min order, categories, etc.)
5. **Fraud Check**: Run fraud prevention checks
6. **Limit Check**: Verify redemption limits not exceeded
7. **Cooldown Check**: Verify cooldown period passed
8. **Reward Issuance**: Create coupon/discount/wallet credit
9. **Record Issuance**: Log reward issuance for tracking

## Reward Tracking

All rewards are tracked in `referral_reward_issuances` table:

- Rule applied
- Attribution used
- Referee and referrer
- Order (if applicable)
- Reward types and values
- Status (pending, issued, reversed)
- Timestamps

## Reversal

Rewards can be reversed if:

- Order is refunded within refund window
- Fraud detected
- Manual admin reversal

```php
$issuance = ReferralRewardIssuance::find($id);
$issuance->reverse('Order refunded');
```

## Integration Points

### Lunar Discount System

Referee discounts integrate with Lunar's discount system:

```php
$discount = Discount::create([
    'name' => "Referral Reward - {$referee->email}",
    'type' => 'percentage',
    'starts_at' => now(),
    'ends_at' => now()->addDays(30),
]);
```

### Wallet System

Referrer store credits use the wallet system:

```php
$wallet = Wallet::firstOrCreate(['user_id' => $referrer->id]);
$wallet->increment('balance', $amount);

WalletTransaction::create([
    'wallet_id' => $wallet->id,
    'type' => WalletTransaction::TYPE_CREDIT,
    'amount' => $amount,
    'reason' => 'referrer_reward',
]);
```

### Coupon System

Both referee and referrer can receive coupons:

```php
Coupon::create([
    'code' => $code,
    'type' => Coupon::TYPE_FIXED,
    'value' => $value,
    'start_at' => now(),
    'end_at' => now()->addDays(30),
    'assigned_to_user_id' => $user->id,
]);
```

## Admin Management

### View Rewards

Admin can view all reward issuances in:
- **Referral Programs** → **Rules** → **Rewards**
- Filter by status, user, rule, date range

### Manual Actions

- **Reverse Reward**: Manually reverse a reward
- **Reissue Reward**: Reissue a reversed reward
- **Adjust Value**: Modify reward value

## Best Practices

1. **Fraud Policy**: Always configure fraud policy for production
2. **Refund Window**: Set appropriate refund window (7-30 days)
3. **Verification**: Require email/phone verification for valuable rewards
4. **Limits**: Set redemption limits to prevent abuse
5. **Cooldown**: Use cooldown periods for repeat rewards
6. **Tiered Rewards**: Use tiered rewards to incentivize multiple referrals
7. **Monitoring**: Regularly review reward issuances for fraud patterns

## Testing

### Test Signup Reward

1. User clicks referral link
2. User signs up
3. Attribution created
4. Signup reward issued (coupon/discount)

### Test Order Reward

1. Referee places order
2. Payment captured
3. Order reward rule matched
4. Referee discount applied
5. Referrer reward issued (store credit/coupon)

### Test Fraud Prevention

1. Attempt self-referral → Blocked
2. Same IP signup → Blocked (if configured)
3. Disposable email → Blocked (if configured)
4. Unverified email → Blocked (if required)

