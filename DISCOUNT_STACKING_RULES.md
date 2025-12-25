# 🎯 Discount Stacking Rules System

A powerful and predictable discount stacking system for handling complex discount scenarios with full compliance and audit trail support.

## Table of Contents

- [Overview](#overview)
- [Discount Types](#discount-types)
- [Stacking Modes](#stacking-modes)
- [Stacking Strategies](#stacking-strategies)
- [Conflict Resolution](#conflict-resolution)
- [Legal & Compliance](#legal--compliance)
- [Usage Examples](#usage-examples)
- [API Reference](#api-reference)

## Overview

The Discount Stacking Rules system provides:

- ✅ **Multiple Discount Types**: Item-level, cart-level, shipping, payment-method, loyalty, coupon-based, and automatic promotions
- ✅ **Flexible Stacking**: Configurable stacking modes (stackable, non-stackable, exclusive)
- ✅ **Smart Strategies**: Best-of, priority-first, cumulative, and exclusive override
- ✅ **Conflict Resolution**: Automatic handling of discount conflicts
- ✅ **Compliance Tracking**: Full audit trail with price-before-discount tracking
- ✅ **Legal Safeguards**: MAP protection, jurisdiction restrictions, anti-double-discount

## Discount Types

### 1. Item-Level Discounts

Apply to specific products or variants.

```php
use App\Enums\DiscountType;
use Lunar\Models\Discount;

$discount = Discount::create([
    'name' => 'Product Sale',
    'type' => 'percentage',
    'data' => [
        'discount_type' => DiscountType::ITEM_LEVEL->value,
        'percentage' => 20,
        'stacking_mode' => 'stackable',
        'stacking_strategy' => 'cumulative',
    ],
]);
```

### 2. Cart-Level Discounts

Apply to the entire cart subtotal.

```php
$discount = Discount::create([
    'name' => 'Cart Discount',
    'coupon' => 'SAVE10',
    'data' => [
        'discount_type' => DiscountType::CART_LEVEL->value,
        'percentage' => 10,
        'stacking_mode' => 'non_stackable',
        'stacking_strategy' => 'best_of',
    ],
]);
```

### 3. Shipping Discounts

Apply to shipping costs only.

```php
$discount = Discount::create([
    'name' => 'Free Shipping',
    'data' => [
        'discount_type' => DiscountType::SHIPPING->value,
        'shipping_discount' => true,
        'fixed_amount' => 0, // Free shipping
        'stacking_mode' => 'exclusive',
    ],
]);
```

### 4. Payment Method Discounts

Apply discounts based on payment method.

```php
$discount = Discount::create([
    'name' => 'PayPal Discount',
    'data' => [
        'discount_type' => DiscountType::PAYMENT_METHOD->value,
        'payment_method_discount' => true,
        'payment_method' => 'paypal',
        'percentage' => 5,
    ],
]);
```

### 5. Customer Loyalty Discounts

Apply based on customer loyalty status.

```php
$discount = Discount::create([
    'name' => 'VIP Discount',
    'data' => [
        'discount_type' => DiscountType::CUSTOMER_LOYALTY->value,
        'loyalty_discount' => true,
        'percentage' => 15,
        'stacking_mode' => 'stackable',
    ],
]);
```

### 6. Coupon-Based Discounts

Manual coupon codes entered by customers.

```php
$discount = Discount::create([
    'name' => 'Coupon Code',
    'coupon' => 'WELCOME20',
    'data' => [
        'discount_type' => DiscountType::COUPON_BASED->value,
        'percentage' => 20,
        'manual_override_auto' => true, // Override automatic promotions
    ],
]);
```

### 7. Automatic Promotions

Automatically applied promotions without coupon codes.

```php
$discount = Discount::create([
    'name' => 'Automatic Sale',
    'data' => [
        'discount_type' => DiscountType::AUTOMATIC_PROMOTION->value,
        'percentage' => 10,
        'stacking_mode' => 'non_stackable',
    ],
]);
```

## Stacking Modes

### Stackable

Discounts can be combined with other stackable discounts.

```php
'stacking_mode' => 'stackable'
```

**Example**: 10% item discount + 5% cart discount = 15% total discount

### Non-Stackable

Discount replaces previous non-stackable discounts of the same type.

```php
'stacking_mode' => 'non_stackable'
```

**Example**: Two 10% cart discounts → only the highest priority one applies

### Exclusive

Discount prevents all other discounts from applying.

```php
'stacking_mode' => 'exclusive'
```

**Example**: Exclusive discount → no other discounts can be applied

## Stacking Strategies

### Best-Of (Choose Max Discount)

Selects the single highest discount amount.

```php
'stacking_strategy' => 'best_of'
```

**Use Case**: When you want customers to get the best single discount, not combinations.

### Priority-First

Applies discounts in priority order until one is exclusive.

```php
'stacking_strategy' => 'priority_first'
```

**Use Case**: Default strategy - applies discounts by priority, stops at exclusive.

### Cumulative

Applies all applicable stackable discounts together.

```php
'stacking_strategy' => 'cumulative'
```

**Use Case**: When you want discounts to stack and combine.

### Exclusive Override

Exclusive discounts override all others; otherwise cumulative.

```php
'stacking_strategy' => 'exclusive_override'
```

**Use Case**: Flexible strategy that prioritizes exclusives but allows stacking otherwise.

## Conflict Resolution

The system automatically resolves conflicts using these rules:

### 1. Highest Priority Wins

Discounts are sorted by priority (higher = more priority). Higher priority discounts are applied first.

```php
$discount->priority = 10; // High priority
```

### 2. Manual Coupons Override Auto Promos

When `manual_override_auto` is enabled (default: true), manual coupon codes override automatic promotions.

```php
'manual_override_auto' => true
```

### 3. B2B Contracts Override Promotions

B2B contract discounts override regular promotions by default.

```php
'b2b_contract' => true
```

### 4. MAP-Protected Variants Block Discounts

If a product variant is MAP-protected, discounts cannot be applied.

```php
// On ProductVariant
'data' => [
    'map_protected' => true,
]
```

### 5. Shipping Discounts Don't Affect Tax Base

Shipping discounts are excluded from tax calculations by default.

```php
'shipping_discounts_affect_tax_base' => false
```

## Legal & Compliance

### Price-Before-Discount Tracking

The system automatically tracks prices before discounts are applied for compliance.

```php
// Automatically tracked in DiscountAuditTrail
'price_before_discount' => 10000,
'discount_amount' => 1000,
'price_after_discount' => 9000,
```

### Discount Reason Logging

Every discount application includes a reason for audit purposes.

```php
'reason' => 'Priority-first strategy: stackable discount applied'
```

### Promotion Audit Trails

Full audit trail stored in `discount_audit_trails` table.

```php
use App\Models\DiscountAuditTrail;

$trails = DiscountAuditTrail::where('cart_id', $cart->id)->get();
```

### Jurisdiction-Specific Rules

Discounts can be restricted to specific jurisdictions.

```php
'jurisdiction' => 'US' // Only valid in US
```

### Anti-Double-Discount Safeguards

Prevents the same discount from being applied multiple times.

```php
'prevent_double_discount' => true
```

## Usage Examples

### Example 1: Stackable Item + Cart Discounts

```php
// Item discount: 10% off specific products
$itemDiscount = Discount::create([
    'name' => 'Product Sale',
    'data' => [
        'discount_type' => 'item_level',
        'percentage' => 10,
        'stacking_mode' => 'stackable',
        'stacking_strategy' => 'cumulative',
    ],
]);

// Cart discount: 5% off entire cart
$cartDiscount = Discount::create([
    'name' => 'Cart Discount',
    'coupon' => 'CART5',
    'data' => [
        'discount_type' => 'cart_level',
        'percentage' => 5,
        'stacking_mode' => 'stackable',
        'stacking_strategy' => 'cumulative',
    ],
]);

// Result: 10% item discount + 5% cart discount = 15% total
```

### Example 2: Exclusive Discount

```php
$exclusiveDiscount = Discount::create([
    'name' => 'Flash Sale',
    'coupon' => 'FLASH50',
    'priority' => 100, // Very high priority
    'data' => [
        'discount_type' => 'cart_level',
        'percentage' => 50,
        'stacking_mode' => 'exclusive',
        'stacking_strategy' => 'exclusive_override',
    ],
]);

// Result: Only this discount applies, all others blocked
```

### Example 3: Best-Of Strategy

```php
// Multiple discounts, but only best one applies
$discount1 = Discount::create([
    'name' => '10% Off',
    'data' => [
        'percentage' => 10,
        'stacking_strategy' => 'best_of',
    ],
]);

$discount2 = Discount::create([
    'name' => '€20 Off',
    'data' => [
        'fixed_amount' => 2000,
        'stacking_strategy' => 'best_of',
    ],
]);

// Result: System chooses whichever gives higher discount
```

### Example 4: B2B Contract Override

```php
$b2bDiscount = Discount::create([
    'name' => 'B2B Contract Price',
    'data' => [
        'discount_type' => 'cart_level',
        'b2b_contract' => true,
        'percentage' => 25,
        'stacking_mode' => 'exclusive',
    ],
]);

// Result: Overrides all regular promotions
```

### Example 5: MAP-Protected Product

```php
// Product variant with MAP protection
$variant = ProductVariant::create([
    'data' => [
        'map_protected' => true,
    ],
]);

// Any discount attempts will be blocked
```

## API Reference

### DiscountStackingService

Main service for applying discounts with stacking rules.

```php
use App\Services\DiscountStacking\DiscountStackingService;

$service = app(DiscountStackingService::class);

$result = $service->applyDiscounts(
    discounts: $discounts,
    cart: $cart,
    baseAmount: 10000,
    scope: 'cart',
);

// $result->applications: Collection of DiscountApplication
// $result->totalDiscount: int
// $result->remainingAmount: int
// $result->appliedRules: array
```

### DiscountAuditService

Service for logging and tracking discount applications.

```php
use App\Services\DiscountStacking\DiscountAuditService;

$auditService = app(DiscountAuditService::class);

// Log single application
$auditService->logApplication(
    application: $application,
    cart: $cart,
    priceBeforeDiscount: 10000,
    priceAfterDiscount: 9000,
    scope: 'cart',
);

// Get audit trail
$trails = $auditService->getAuditTrail($discount);
$cartTrails = $auditService->getCartAuditTrail($cart);
```

### DiscountComplianceService

Service for compliance checks and validation.

```php
use App\Services\DiscountStacking\DiscountComplianceService;

$complianceService = app(DiscountComplianceService::class);

// Validate compliance
$violations = $complianceService->validateCompliance($discount, $cart);

// Generate compliance report
$report = $complianceService->generateComplianceReport($cart);
```

## Configuration

Edit `config/discounts.php` to customize behavior:

```php
return [
    'default_stacking_mode' => 'non_stackable',
    'default_stacking_strategy' => 'priority_first',
    'manual_coupons_override_auto' => true,
    'b2b_contracts_override_promotions' => true,
    'require_audit_trail' => false,
    'track_price_before_discount' => true,
    'prevent_double_discount' => true,
];
```

## Database Schema

### Discounts Table Additions

- `stacking_mode`: string (stackable, non_stackable, exclusive)
- `stacking_strategy`: string (best_of, priority_first, cumulative, exclusive_override)
- `max_discount_cap`: integer
- `map_protected`: boolean
- `b2b_contract`: boolean
- `manual_override_auto`: boolean
- `jurisdiction`: string
- `track_price_before_discount`: boolean
- `log_discount_reason`: boolean
- `require_audit_trail`: boolean

### Discount Audit Trails Table

Tracks all discount applications with full metadata for compliance and auditing.

## Best Practices

1. **Set Priorities**: Use priority to control application order
2. **Use Exclusive Sparingly**: Exclusive discounts block all others
3. **Enable Audit Trails**: For compliance, enable audit trails
4. **Test Stacking**: Test combinations to ensure expected behavior
5. **MAP Protection**: Mark MAP-protected products to prevent discount violations
6. **Jurisdiction Rules**: Set jurisdiction restrictions for region-specific discounts

## Troubleshooting

### Discounts Not Stacking

- Check `stacking_mode` is set to `stackable`
- Verify `stacking_strategy` allows stacking
- Check for exclusive discounts blocking others

### Wrong Discount Applied

- Check priorities (higher = more priority)
- Verify conflict resolution rules
- Check for MAP protection blocking discounts

### Audit Trail Missing

- Enable `require_audit_trail` on discount
- Check `track_price_before_discount` setting
- Verify `log_discount_reason` is enabled

---

**Happy discounting! 🎉**

