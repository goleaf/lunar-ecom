# Referral System V2 - Complete Data Model Implementation

## Overview

This document describes the complete referral system implementation based on the detailed data model specification. The system supports both coupon-based and wallet-based rewards, comprehensive fraud detection, and full audit logging.

## Database Schema

### Core Tables

#### 1. Users & Groups

**users** (extended)
- `phone` - Unique phone number
- `status` - active/banned
- `group_id` - Foreign key to user_groups
- `referral_code` - Unique human-friendly code
- `referral_link_slug` - Optional URL-friendly slug
- `referred_by_user_id` - Who referred this user
- `referred_at` - When user was referred
- `referral_blocked` - Block referral rewards

**user_groups**
- `name` - Group name
- `type` - B2C, B2B, VIP, Staff, Partner, Other
- `default_discount_stack_policy` - Default stacking policy

#### 2. Referral Program

**referral_programs**
- `name` - Program name
- `handle` - Unique identifier
- `status` - draft/active/paused/archived
- `start_at` / `end_at` - Validity period
- `channel_ids` - JSON array of channel IDs
- `currency_scope` - all/specific
- `currency_ids` - JSON array (if specific)
- `audience_scope` - all/users/groups
- `audience_user_ids` - JSON array (if users)
- `audience_group_ids` - JSON array (if groups)
- `terms_url` - Terms and conditions URL
- `description` - Program description

**referral_rules**
- `referral_program_id` - Parent program
- `trigger_event` - signup, first_order_paid, nth_order_paid, etc.
- `nth_order` - For nth_order_paid trigger
- `referee_reward_type` - coupon, percentage_discount, fixed_discount, free_shipping, store_credit
- `referee_reward_value` - Reward value
- `referrer_reward_type` - coupon, store_credit, percentage_discount_next_order, fixed_amount
- `referrer_reward_value` - Reward value
- `min_order_total` - Minimum order amount
- `eligible_product_ids` - JSON array
- `eligible_category_ids` - JSON array
- `eligible_collection_ids` - JSON array
- `max_redemptions_total` - Total limit
- `max_redemptions_per_referrer` - Per referrer limit
- `max_redemptions_per_referee` - Per referee limit
- `cooldown_days` - Days between redemptions
- `stacking_mode` - exclusive/stackable/best_of
- `priority` - Rule priority
- `validation_window_days` - Referee must order within X days
- `fraud_policy_id` - Associated fraud policy

#### 3. Tracking & Attribution

**referral_clicks**
- `referrer_user_id` - Who owns the code
- `referral_code` - Code that was clicked
- `ip_hash` - Hashed IP address
- `user_agent_hash` - Hashed user agent
- `landing_url` - Where user landed
- `session_id` - Session identifier

**referral_attributions**
- `referee_user_id` - User who was referred
- `referrer_user_id` - User who referred
- `program_id` - Referral program
- `code_used` - Code that was used
- `attributed_at` - When attribution happened
- `attribution_method` - code/link/manual_admin
- `status` - pending/confirmed/rejected
- `rejection_reason` - Why rejected (if applicable)

#### 4. Rewards

**coupons**
- `code` - Unique coupon code
- `type` - percentage/fixed/free_shipping
- `value` - Discount value
- `start_at` / `end_at` - Validity period
- `usage_limit` - Total usage limit
- `per_user_limit` - Per user limit
- `eligible_product_ids` - JSON array
- `eligible_category_ids` - JSON array
- `eligible_collection_ids` - JSON array
- `stack_policy` - Stacking policy
- `created_by_rule_id` - Which rule created this
- `assigned_to_user_id` - User-specific coupon
- `is_active` - Active status

**coupon_redemptions**
- `coupon_id` - Coupon used
- `user_id` - User who redeemed
- `order_id` - Order where redeemed
- `redeemed_at` - When redeemed
- `discount_amount` - Amount discounted

**wallets**
- `user_id` - Owner
- `balance` - Available balance
- `pending_balance` - Held balance

**wallet_transactions**
- `wallet_id` - Wallet
- `type` - credit/debit/hold/release
- `amount` - Transaction amount
- `reason` - referral_reward, refund_adjustment, fraud_reversal, etc.
- `related_order_id` - Related order
- `related_referral_id` - Related referral attribution

#### 5. Fraud & Compliance

**fraud_policies**
- `name` - Policy name
- `allow_same_ip` - Allow same IP referrals
- `max_signups_per_ip_per_day` - IP signup limit
- `max_orders_per_ip_per_day` - IP order limit
- `block_disposable_emails` - Block disposable emails
- `block_same_card_fingerprint` - Block same card
- `require_email_verified` - Require verified email
- `require_phone_verified` - Require verified phone
- `min_account_age_days_before_reward` - Minimum account age
- `manual_review_threshold` - Risk score threshold
- `custom_rules` - JSON custom rules

**referral_audit_logs**
- `actor_type` - admin/user/system
- `actor_id` - Actor ID
- `action` - Action performed
- `subject_type` / `subject_id` - What was changed (polymorphic)
- `before` - Before state (JSON)
- `after` - After state (JSON)
- `notes` - Additional notes
- `ip_address` - IP address
- `user_agent` - User agent

## Models

### User Model Extensions

```php
// New relationships
$user->group() // UserGroup
$user->referrer() // User who referred
$user->referees() // Users referred by this user
$user->wallet() // Wallet
$user->coupons() // Assigned coupons
$user->referralAttributions() // As referee
$user->referralClicks() // As referrer

// New methods
$user->generateReferralCode() // Generate unique code
$user->getReferralLink() // Get referral URL
```

### ReferralProgram Model

```php
// Relationships
$program->rules() // ReferralRule[]
$program->activeRules() // Active rules only
$program->attributions() // ReferralAttribution[]
$program->analytics() // ReferralAnalytics[]

// Methods
$program->isCurrentlyActive() // Check if active
$program->isEligibleForUser($user) // Check eligibility
```

### ReferralRule Model

```php
// Relationships
$rule->program() // ReferralProgram
$rule->fraudPolicy() // FraudPolicy
$rule->coupons() // Coupons created by this rule

// Constants
ReferralRule::TRIGGER_SIGNUP
ReferralRule::TRIGGER_FIRST_ORDER_PAID
ReferralRule::TRIGGER_NTH_ORDER_PAID
ReferralRule::TRIGGER_SUBSCRIPTION_STARTED

ReferralRule::REWARD_COUPON
ReferralRule::REWARD_STORE_CREDIT
ReferralRule::REWARD_PERCENTAGE_DISCOUNT
// etc.
```

### Coupon Model

```php
// Relationships
$coupon->rule() // ReferralRule
$coupon->assignedUser() // User
$coupon->redemptions() // CouponRedemption[]

// Methods
$coupon->isValid() // Check validity
$coupon->canBeUsedBy($user) // Check if user can use
```

### Wallet Model

```php
// Relationships
$wallet->user() // User
$wallet->transactions() // WalletTransaction[]

// Methods
$wallet->credit($amount, $reason, $metadata)
$wallet->debit($amount, $reason, $metadata)
$wallet->hold($amount, $reason, $metadata)
$wallet->release($amount, $reason, $metadata)
```

## Key Features

### 1. Flexible Reward System

**Coupon-Based Rewards:**
- Percentage discounts
- Fixed amount discounts
- Free shipping
- Product/category/collection restrictions
- Usage limits (total and per-user)
- Stacking policies

**Wallet-Based Rewards:**
- Store credit system
- Hold/release mechanism for pending transactions
- Transaction history with reasons
- Integration with orders and referrals

### 2. Comprehensive Tracking

- **Referral Clicks**: Track every click with IP/user agent hashing
- **Attributions**: Track who referred whom with multiple methods
- **Status Management**: pending/confirmed/rejected workflow
- **Audit Logs**: Complete audit trail for compliance

### 3. Fraud Prevention

- IP-based limits
- Disposable email blocking
- Card fingerprint detection
- Email/phone verification requirements
- Account age requirements
- Manual review thresholds
- Custom rule support

### 4. Rule-Based System

- Multiple trigger events (signup, first order, nth order, subscription)
- Separate referee and referrer rewards
- Product/category/collection eligibility
- Order value minimums
- Redemption limits (total, per referrer, per referee)
- Cooldown periods
- Validation windows
- Priority-based rule application
- Stacking modes (exclusive, stackable, best-of)

## Migration Path

### Step 1: Run Migrations

```bash
php artisan migrate
```

This will:
1. Add referral fields to users table
2. Create user_groups table
3. Update referral_programs table
4. Create referral_rules table
5. Create referral_clicks table
6. Create referral_attributions table
7. Create coupons and coupon_redemptions tables
8. Create wallets and wallet_transactions tables
9. Create fraud_policies table
10. Create referral_audit_logs table

### Step 2: Data Migration (if needed)

If you have existing referral data, you'll need to:
1. Migrate old referral codes to new structure
2. Convert old rewards to coupons or wallet credits
3. Create attributions from old events

### Step 3: Update Services

Services need to be updated to work with:
- ReferralAttribution instead of ReferralEvent
- Coupon/Wallet instead of ReferralReward
- ReferralRule instead of inline program config

## Next Steps

1. **Update Services**: Refactor ReferralService, ReferralRewardService to use new models
2. **Update Admin Resources**: Update Filament resources for new structure
3. **Create Fraud Service**: Implement fraud detection logic
4. **Create Attribution Service**: Handle attribution workflow
5. **Update API Endpoints**: Update to use new models
6. **Create Tests**: Comprehensive test suite

## Usage Examples

### Creating a Referral Program with Rules

```php
$program = ReferralProgram::create([
    'name' => 'Summer Referral Program',
    'handle' => 'summer2024',
    'status' => ReferralProgram::STATUS_ACTIVE,
    'start_at' => now(),
    'end_at' => now()->addMonths(3),
    'audience_scope' => ReferralProgram::AUDIENCE_SCOPE_ALL,
    'currency_scope' => ReferralProgram::CURRENCY_SCOPE_ALL,
]);

// Create rule for signup
$signupRule = ReferralRule::create([
    'referral_program_id' => $program->id,
    'trigger_event' => ReferralRule::TRIGGER_SIGNUP,
    'referee_reward_type' => ReferralRule::REWARD_COUPON,
    'referee_reward_value' => 10, // 10% discount
    'referrer_reward_type' => ReferralRule::REWARD_STORE_CREDIT,
    'referrer_reward_value' => 5.00, // $5 credit
    'stacking_mode' => ReferralRule::STACKING_EXCLUSIVE,
    'priority' => 10,
]);

// Create rule for first order
$firstOrderRule = ReferralRule::create([
    'referral_program_id' => $program->id,
    'trigger_event' => ReferralRule::TRIGGER_FIRST_ORDER_PAID,
    'min_order_total' => 50.00,
    'referee_reward_type' => ReferralRule::REWARD_PERCENTAGE_DISCOUNT,
    'referee_reward_value' => 15, // 15% off
    'referrer_reward_type' => ReferralRule::REWARD_STORE_CREDIT,
    'referrer_reward_value' => 10.00, // $10 credit
    'validation_window_days' => 30,
    'max_redemptions_per_referrer' => 10,
    'stacking_mode' => ReferralRule::STACKING_EXCLUSIVE,
    'priority' => 20,
]);
```

### Tracking a Referral Click

```php
$click = ReferralClick::create([
    'referrer_user_id' => $referrer->id,
    'referral_code' => $referrer->referral_code,
    'ip_hash' => hash('sha256', request()->ip()),
    'user_agent_hash' => hash('sha256', request()->userAgent()),
    'landing_url' => request()->fullUrl(),
    'session_id' => session()->getId(),
]);
```

### Creating Attribution

```php
$attribution = ReferralAttribution::create([
    'referee_user_id' => $newUser->id,
    'referrer_user_id' => $referrer->id,
    'program_id' => $program->id,
    'code_used' => $referrer->referral_code,
    'attributed_at' => now(),
    'attribution_method' => ReferralAttribution::METHOD_LINK,
    'status' => ReferralAttribution::STATUS_PENDING,
]);
```

### Issuing Rewards

**Coupon Reward:**
```php
$coupon = Coupon::create([
    'code' => 'REF10OFF',
    'type' => Coupon::TYPE_PERCENTAGE,
    'value' => 10,
    'start_at' => now(),
    'end_at' => now()->addDays(30),
    'per_user_limit' => 1,
    'created_by_rule_id' => $rule->id,
    'assigned_to_user_id' => $user->id,
]);
```

**Wallet Credit:**
```php
$wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
$transaction = $wallet->credit(
    10.00,
    WalletTransaction::REASON_REFERRAL_REWARD,
    ['referral_attribution_id' => $attribution->id]
);
```

## Admin Panel Updates Needed

1. **ReferralProgramResource**: Update to show rules instead of inline config
2. **ReferralRuleResource**: New resource for managing rules
3. **ReferralAttributionResource**: View and manage attributions
4. **CouponResource**: Manage coupons
5. **WalletResource**: View wallets and transactions
6. **FraudPolicyResource**: Manage fraud policies
7. **ReferralAuditLogResource**: View audit logs

## API Updates Needed

1. Update referral endpoints to use new models
2. Add coupon redemption endpoints
3. Add wallet balance endpoints
4. Add attribution endpoints
5. Add fraud check endpoints


