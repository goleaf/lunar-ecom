# Cart Transparency System

This document describes the cart transparency system that ensures all cart responses always expose:

1. **Subtotal (pre-discount)** - Raw subtotal before any discounts
2. **Total discounts** - Sum of all applied discounts
3. **Tax breakdown** - Detailed breakdown of all taxes
4. **Shipping cost** - Shipping charges with breakdown
5. **Grand total** - Final total amount
6. **Audit trail of applied rules** - Complete history of which discount rules were applied

## Overview

The `CartTransparencyService` ensures that every cart response includes all required transparency fields, regardless of whether discounts, taxes, or shipping are applied. This provides complete visibility into cart calculations.

## Service

### CartTransparencyService

Located at: `app/Services/CartTransparencyService.php`

**Main Method: `getCartBreakdown(?Cart $cart = null)`**

Returns a complete breakdown array with all transparency fields:

```php
use App\Services\CartTransparencyService;

$service = app(CartTransparencyService::class);
$breakdown = $service->getCartBreakdown($cart);
```

**Response Structure:**

```php
[
    'subtotal_pre_discount' => [
        'value' => 10000,        // Integer value in smallest currency unit
        'formatted' => '$100.00', // Formatted for display
        'decimal' => 100.00,     // Decimal value
    ],
    'subtotal_discounted' => [...],
    'total_discounts' => [...],
    'shipping_total' => [...],
    'tax_total' => [...],
    'grand_total' => [...],
    'discount_breakdown' => [
        [
            'name' => 'Summer Sale',
            'description' => '20% off',
            'coupon_code' => 'SUMMER20',
            'amount' => [...],
            'type' => 'percentage',
            'priority' => 1,
            'applied_at' => '2025-12-26T10:00:00Z',
        ],
    ],
    'tax_breakdown' => [
        [
            'name' => 'VAT',
            'description' => 'Value Added Tax',
            'rate' => 20.0,
            'amount' => [...],
            'type' => 'standard',
        ],
    ],
    'shipping_breakdown' => [
        [
            'name' => 'Standard Shipping',
            'identifier' => 'standard',
            'amount' => [...],
            'tax_amount' => [...],
        ],
    ],
    'applied_rules' => [
        [
            'rule_id' => 1,
            'rule_name' => 'Summer Sale',
            'rule_type' => 'percentage',
            'coupon_code' => 'SUMMER20',
            'applied_at' => '2025-12-26T10:00:00Z',
            'status' => 'applied',
            'conditions_met' => [
                [
                    'type' => 'minimum_purchase',
                    'currency' => 'USD',
                    'value' => 5000,
                    'met' => true,
                ],
            ],
        ],
    ],
    'currency' => 'USD',
    'currency_symbol' => '$',
    'item_count' => 3,
    'line_count' => 2,
]
```

## Controller Integration

All cart controller methods now use `CartTransparencyService` to ensure consistent responses:

### CartController Methods

1. **`index()`** - Cart page view
   - Passes `$cartBreakdown` to view
   - View displays all transparency fields

2. **`summary()`** - AJAX cart summary
   - Returns complete breakdown with all fields
   - Always exposes all transparency fields

3. **`add()`** - Add item to cart
   - Returns breakdown in JSON response

4. **`update()`** - Update cart line
   - Returns breakdown in JSON response

5. **`remove()`** - Remove cart line
   - Returns breakdown in JSON response

6. **`applyDiscount()`** - Apply discount
   - Returns breakdown including discount audit trail

7. **`removeDiscount()`** - Remove discount
   - Returns breakdown with updated applied rules

## View Integration

The cart view (`resources/views/frontend/cart/index.blade.php`) displays:

1. **Subtotal (pre-discount)** - Always shown
2. **Total Discounts** - Always shown (even if $0.00)
3. **Discount Breakdown** - Shown if discounts applied
4. **Subtotal (after discount)** - Calculated subtotal
5. **Shipping Cost** - Always shown
6. **Shipping Breakdown** - Shown if shipping applied
7. **Tax Total** - Always shown
8. **Tax Breakdown** - Detailed tax rates
9. **Grand Total** - Final total
10. **Applied Rules Audit Trail** - List of applied discount rules

## API Response Format

All cart API endpoints return consistent structure:

```json
{
    "cart": {
        "item_count": 3,
        "has_items": true,
        "subtotal_pre_discount": {
            "value": 10000,
            "formatted": "$100.00",
            "decimal": 100.00
        },
        "subtotal_discounted": {...},
        "total_discounts": {...},
        "shipping_total": {...},
        "tax_total": {...},
        "grand_total": {...},
        "discount_breakdown": [...],
        "tax_breakdown": [...],
        "shipping_breakdown": [...],
        "applied_rules": [...],
        "currency": "USD",
        "currency_symbol": "$"
    }
}
```

## Features

### Always Exposed Fields

All transparency fields are **always** included in responses, even if their value is zero:

- `subtotal_pre_discount` - Always present
- `total_discounts` - Always present (0 if no discounts)
- `shipping_total` - Always present (0 if no shipping)
- `tax_total` - Always present (0 if no tax)
- `grand_total` - Always present

### Audit Trail

The audit trail (`applied_rules`) includes:

- Rule ID and name
- Rule type (percentage, fixed, etc.)
- Coupon code (if applicable)
- Applied timestamp
- Status (applied, failed, etc.)
- Conditions met (minimum purchase, customer group, etc.)

### Breakdown Details

Each breakdown provides detailed information:

- **Discount Breakdown**: Individual discounts with amounts
- **Tax Breakdown**: Tax rates and amounts per tax type
- **Shipping Breakdown**: Shipping options with costs and tax

## Usage Examples

### Get Cart Breakdown

```php
use App\Services\CartTransparencyService;

$service = app(CartTransparencyService::class);
$breakdown = $service->getCartBreakdown();

// Access fields
$subtotal = $breakdown['subtotal_pre_discount']['formatted'];
$discounts = $breakdown['total_discounts']['formatted'];
$tax = $breakdown['tax_total']['formatted'];
$shipping = $breakdown['shipping_total']['formatted'];
$total = $breakdown['grand_total']['formatted'];
```

### Check Applied Rules

```php
$appliedRules = $breakdown['applied_rules'];

foreach ($appliedRules as $rule) {
    echo "Rule: {$rule['rule_name']}\n";
    echo "Coupon: {$rule['coupon_code']}\n";
    echo "Applied: {$rule['applied_at']}\n";
}
```

### Get Discount Details

```php
$discountBreakdown = $breakdown['discount_breakdown'];

foreach ($discountBreakdown as $discount) {
    echo "{$discount['name']}: -{$discount['amount']['formatted']}\n";
}
```

## Benefits

1. **Complete Transparency** - Customers see exactly how totals are calculated
2. **Audit Trail** - Full history of applied discount rules
3. **Consistent API** - All endpoints return same structure
4. **Debugging** - Easy to trace calculation issues
5. **Compliance** - Meets requirements for financial transparency

## Testing

Test cart transparency by:

1. Adding items to cart
2. Applying discounts
3. Setting shipping address
4. Checking all fields are present in response
5. Verifying audit trail shows applied rules

## Notes

- All prices are stored as integers (smallest currency unit)
- Formatted values use currency formatting
- Decimal values are for calculations
- Breakdown arrays are empty if no items in that category
- Applied rules array is empty if no discounts applied

