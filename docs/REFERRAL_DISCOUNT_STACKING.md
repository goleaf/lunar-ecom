# Referral Discount Stacking & Priority System

## Overview

This document describes the discount stacking and priority system for referral discounts, ensuring proper interaction with other promotions and discounts.

## Stacking Policies

### 1. Exclusive Mode

**Behavior**: Referral discount cannot stack with other promotions.

**How it works**:
- When referral discount is applied, all other discounts are removed
- Only the referral discount remains active
- Best for: Simple referral programs where referral discount should be the only discount

**Example**:
- Cart has 10% promo code
- User applies referral discount (15%)
- Result: Promo code removed, only 15% referral discount applied

### 2. Best-Of Mode

**Behavior**: System automatically chooses the largest discount.

**How it works**:
- Compares referral discount value with all other discounts
- Applies only the discount with highest value
- Best for: Ensuring customers always get the best deal

**Example**:
- Cart has 10% promo code (worth €10)
- User has referral discount (15% worth €12)
- Result: 15% referral discount applied, promo code removed

### 3. Stackable Mode

**Behavior**: Allow stacking with caps to prevent excessive discounts.

**How it works**:
- Referral discount stacks with other discounts
- Respects maximum total discount percentage cap
- Respects maximum total discount amount cap
- Best for: Flexible discount strategies

**Example**:
- Cart has 10% promo code
- User has referral discount (15%)
- Max total discount cap: 20%
- Result: Both discounts applied, total = 20% (capped)

## Configuration

### Program-Level Configuration

Set default stacking mode for all rules in a program:

```php
ReferralProgram::create([
    'name' => 'Summer Referral',
    'default_stacking_mode' => ReferralRule::STACKING_STACKABLE,
    'max_total_discount_percent' => 25.00, // Max 25% total
    'max_total_discount_amount' => 50.00,  // Max €50 total
    'apply_before_tax' => true,
    'shipping_discount_stacks' => false,
]);
```

### Rule-Level Configuration

Override program defaults for specific rules:

```php
ReferralRule::create([
    'referral_program_id' => $program->id,
    'trigger_event' => ReferralRule::TRIGGER_FIRST_ORDER_PAID,
    'stacking_mode' => ReferralRule::STACKING_EXCLUSIVE, // Override program default
    'max_total_discount_percent' => 20.00, // Rule-specific cap
    'apply_before_tax' => true,
    'shipping_discount_stacks' => false,
]);
```

## Additional Settings

### Max Total Discount Per Order

**Percentage Cap**:
- Limits total discount percentage when stacking
- Example: Max 20% means even if discounts total 30%, only 20% is applied

**Amount Cap**:
- Limits total discount amount in currency
- Example: Max €50 means even if discounts total €75, only €50 is applied

**Priority**: Rule-level caps override program-level caps

### Apply Before Tax

**When enabled** (default):
- Discount applies to subtotal before tax calculation
- Tax is calculated on discounted amount
- Example: €100 order, 10% discount = €90 subtotal, tax on €90

**When disabled**:
- Discount applies after tax calculation
- Tax is calculated on full amount, discount applied to total
- Example: €100 order + €20 tax = €120, 10% discount = €108

### Shipping Discount Stacks

**When enabled**:
- Shipping discounts can stack with referral discounts
- Example: Free shipping + 10% referral = both apply

**When disabled** (default):
- Shipping discounts are excluded from stacking
- Only product discounts stack
- Example: Free shipping + 10% referral = only 10% applies to products

## Usage

### Apply Discount with Stacking

```php
use App\Services\ReferralDiscountStackingService;

$stackingService = app(ReferralDiscountStackingService::class);

// Apply referral discount to cart
$result = $stackingService->applyReferralDiscount(
    $cart,
    $rule,
    $user
);

if ($result['applied']) {
    echo "Discount applied: {$result['mode']}";
} else {
    echo "Not applied: {$result['reason']}";
}
```

### Check Stacking Settings

```php
// Check if applies before tax
$beforeTax = $stackingService->appliesBeforeTax($rule);

// Check if shipping stacks
$shippingStacks = $stackingService->shippingDiscountStacks($rule);
```

## Integration with Lunar

The stacking service integrates with Lunar's discount system:

1. **Discount Creation**: Referral discounts are created as Lunar Discount models
2. **Cart Application**: Discounts are attached to cart via Lunar's discount relationships
3. **Automatic Calculation**: Lunar handles discount calculation based on stacking rules

### Discount Data Structure

Referral discounts include stacking metadata:

```php
$discount->data = [
    'value' => 10.00,
    'min_basket' => 50.00,
    'stacking_mode' => 'stackable',
    'max_total_discount_percent' => 20.00,
    'max_total_discount_amount' => 50.00,
    'apply_before_tax' => true,
    'shipping_discount_stacks' => false,
];
```

## Examples

### Example 1: Exclusive Mode

**Setup**:
- Program: `default_stacking_mode = 'exclusive'`
- Cart: Has 15% promo code
- Referral: 10% discount

**Result**:
- Promo code removed
- Only 10% referral discount applied

### Example 2: Best-Of Mode

**Setup**:
- Program: `default_stacking_mode = 'best_of'`
- Cart: Has 10% promo code (€10 value)
- Referral: 15% discount (€15 value)

**Result**:
- Promo code removed
- 15% referral discount applied (higher value)

### Example 3: Stackable with Cap

**Setup**:
- Program: `default_stacking_mode = 'stackable'`, `max_total_discount_percent = 20`
- Cart: Has 10% promo code
- Referral: 15% discount

**Result**:
- Both discounts applied
- Total discount: 20% (capped at max)
- Promo: 10%, Referral: 10% (adjusted to fit cap)

### Example 4: Stackable with Amount Cap

**Setup**:
- Program: `default_stacking_mode = 'stackable'`, `max_total_discount_amount = 50`
- Cart: €200 order
- Cart: Has 10% promo code (€20 value)
- Referral: 15% discount (€30 value)

**Result**:
- Both discounts applied
- Total discount: €50 (capped at max)
- Promo: €20, Referral: €30

## Admin Configuration

### Program Settings

In **Referral Programs** → **Edit Program**:

1. **Default Stacking Mode**: Choose exclusive, best-of, or stackable
2. **Max Total Discount (%)**: Set percentage cap (optional)
3. **Max Total Discount Amount**: Set amount cap (optional)
4. **Apply Before Tax**: Toggle tax calculation order
5. **Shipping Discount Stacks**: Toggle shipping stacking

### Rule Settings

In **Referral Programs** → **Rules** → **Edit Rule**:

1. **Stacking Mode**: Override program default (optional)
2. **Max Total Discount (%)**: Rule-specific cap (optional)
3. **Max Total Discount Amount**: Rule-specific cap (optional)
4. **Apply Before Tax**: Override program setting (optional)
5. **Shipping Discount Stacks**: Override program setting (optional)

## Best Practices

1. **Exclusive Mode**: Use for high-value referral discounts (15%+) where stacking would be too generous
2. **Best-Of Mode**: Use to ensure customers always get best deal without confusion
3. **Stackable Mode**: Use with caps to allow flexibility while preventing abuse
4. **Caps**: Always set caps in stackable mode to prevent excessive discounts
5. **Before Tax**: Usually better to apply before tax for customer clarity
6. **Shipping**: Keep shipping stacking disabled unless specifically needed

## Testing

### Test Exclusive Mode

1. Add promo code to cart
2. Apply referral discount
3. Verify promo code removed
4. Verify only referral discount active

### Test Best-Of Mode

1. Add promo code (10%)
2. Apply referral discount (15%)
3. Verify 15% discount applied
4. Verify promo code removed

### Test Stackable Mode

1. Add promo code (10%)
2. Apply referral discount (15%)
3. Set max cap to 20%
4. Verify total discount = 20%
5. Verify both discounts active (adjusted)


