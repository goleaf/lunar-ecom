# 🎁 Advanced Discounts with Lunar

Lunar's flexible discount system lets you create rules-based promotions with ease. This guide covers all the advanced discount features available in this implementation.

## Table of Contents

- [Overview](#overview)
- [Discount Types](#discount-types)
- [Using DiscountService](#using-discountservice)
- [Examples](#examples)
- [DiscountHelper Methods](#discounthelper-methods)
- [Admin Panel Integration](#admin-panel-integration)

## Overview

The advanced discount system provides:

- ✅ **Custom Discounts**: Percentage-based or fixed discounts
- ✅ **Promo Codes**: Coupon codes with specific rules (expiry, usage limits)
- ✅ **Targeted Promos**: Apply discounts to specific products, categories, or users
- ✅ **Conditions**: Set conditions like min. cart value, specific days, user groups
- ✅ **BOGO Discounts**: Buy-one-get-one deals
- ✅ **Time-based Discounts**: Apply discounts on specific days/hours

## Discount Types

### 1. Percentage Discount
Applies a percentage discount to the cart, with optional minimum cart value and maximum discount cap.

### 2. Fixed Amount Discount
Applies a fixed amount discount (e.g., €10 off), with optional minimum cart value.

### 3. BOGO (Buy One Get One) Discount
Buy-one-get-one deals with configurable quantities and discount percentages.

### 4. Category Discount
Applies discounts to products in specific categories.

### 5. Product Discount
Applies discounts to specific products or product variants.

## Using DiscountService

The `DiscountService` class provides a fluent builder interface for creating discounts:

```php
use App\Services\DiscountService;
```

### Basic Usage Pattern

```php
$discount = DiscountService::percentageDiscount('Discount Name', 'unique_handle')
    ->percentage(10)
    ->minCartValue(5000) // €50 in cents
    ->couponCode('SAVE10')
    ->startsAt(now())
    ->endsAt(now()->addDays(30))
    ->maxUses(100)
    ->create();
```

## Examples

### Example 1: 10% Off Orders Over €50

```php
use App\Services\DiscountService;
use Carbon\Carbon;

$discount = DiscountService::percentageDiscount('10% Off Over €50', '10_percent_over_50')
    ->percentage(10)
    ->minCartValue(5000) // €50 in cents
    ->couponCode('SAVE10')
    ->startsAt(now())
    ->endsAt(Carbon::parse('2024-12-31'))
    ->maxUses(1000)
    ->priority(5)
    ->create();
```

### Example 2: Fixed €15 Off with Minimum Purchase

```php
$discount = DiscountService::fixedAmountDiscount('€15 Off', '15_off_discount')
    ->fixedAmount(1500) // €15 in cents
    ->minCartValue(10000) // €100 minimum
    ->couponCode('SAVE15')
    ->startsAt(now())
    ->endsAt(now()->addMonth())
    ->maxUses(500)
    ->create();
```

### Example 3: Buy One Get One Free

```php
use App\Lunar\Models\ProductVariant;

$productVariant = ProductVariant::find(1);

$discount = DiscountService::bogoDiscount('BOGO Sale', 'bogo_sale')
    ->requireProducts([$productVariant]) // Product that must be in cart
    ->withData([
        'buy_quantity' => 1,
        'get_quantity' => 1,
        'get_discount' => 100, // 100% = free
    ])
    ->couponCode('BOGO')
    ->startsAt(now())
    ->endsAt(now()->addWeek())
    ->create();
```

### Example 4: Category-Specific Discount

```php
use App\Lunar\Models\Category;

$category = Category::find(1);

$discount = DiscountService::categoryDiscount('Electronics Sale', 'electronics_sale')
    ->applyToCategories([$category])
    ->percentage(20)
    ->minCartValue(10000) // €100 minimum
    ->couponCode('ELECTRONICS20')
    ->startsAt(now())
    ->create();
```

### Example 5: Product-Specific Discount

```php
use App\Lunar\Models\ProductVariant;

$productVariants = ProductVariant::whereIn('id', [1, 2, 3])->get();

$discount = DiscountService::productDiscount('Featured Products', 'featured_products')
    ->applyToProducts($productVariants)
    ->percentage(25)
    ->couponCode('FEATURED25')
    ->startsAt(now())
    ->create();
```

### Example 6: Weekend Only Discount

```php
$discount = DiscountService::percentageDiscount('Weekend Special', 'weekend_special')
    ->percentage(15)
    ->allowedDays([0, 6]) // Sunday (0) and Saturday (6)
    ->couponCode('WEEKEND15')
    ->startsAt(now())
    ->endsAt(now()->addMonth())
    ->create();
```

### Example 7: Time-Window Discount (9 AM - 5 PM)

```php
$discount = DiscountService::fixedAmountDiscount('Lunch Special', 'lunch_special')
    ->fixedAmount(500) // €5 off
    ->allowedTimeWindow('09:00', '17:00')
    ->couponCode('LUNCH5')
    ->startsAt(now())
    ->endsAt(now()->addWeek())
    ->create();
```

### Example 8: Customer Group Discount

```php
use App\Lunar\Models\CustomerGroup;

$vipGroup = CustomerGroup::where('handle', 'vip')->first();

$discount = DiscountService::percentageDiscount('VIP Discount', 'vip_discount')
    ->percentage(20)
    ->forCustomerGroups([$vipGroup])
    ->startsAt(now())
    ->create();
```

### Example 9: Discount with Maximum Cap

```php
$discount = DiscountService::percentageDiscount('20% Off Max €50', '20_percent_max_50')
    ->percentage(20)
    ->maxDiscountAmount(5000) // Maximum €50 discount
    ->minCartValue(10000) // Minimum €100 purchase
    ->couponCode('SAVE20MAX')
    ->startsAt(now())
    ->create();
```

### Example 10: Complex Discount with Multiple Conditions

```php
use App\Lunar\Models\ProductVariant;
use App\Lunar\Models\Category;

$requiredProducts = ProductVariant::whereIn('id', [1, 2])->get();
$targetCategory = Category::find(3);

$discount = DiscountService::percentageDiscount('Bundle Discount', 'bundle_discount')
    ->percentage(15)
    ->requireProducts($requiredProducts) // Must have these products
    ->applyToCategories([$targetCategory]) // Apply discount to this category
    ->minCartValue(50000) // Minimum €500
    ->maxDiscountAmount(10000) // Maximum €100 discount
    ->couponCode('BUNDLE15')
    ->priority(10) // High priority
    ->stop(true) // Stop other discounts
    ->startsAt(now())
    ->endsAt(now()->addMonth())
    ->maxUses(100)
    ->create();
```

## DiscountHelper Methods

The `DiscountHelper` class provides utility methods for working with discounts:

### Finding Discounts

```php
use App\Lunar\Discounts\DiscountHelper;

// Find by ID
$discount = DiscountHelper::find(1);

// Find by handle
$discount = DiscountHelper::findByHandle('10_percent_over_50');

// Find by coupon code
$discount = DiscountHelper::findByCouponCode('SAVE10');

// Get active discounts
$activeDiscounts = DiscountHelper::getActive();

// Get usable discounts (not exhausted)
$usableDiscounts = DiscountHelper::getUsable();

// Get available discounts (active + usable)
$availableDiscounts = DiscountHelper::getAvailable();
```

### Checking Discount Validity

```php
// Check if discount is active (within date range)
$isActive = DiscountHelper::isActive($discount);

// Check if discount can be used (not exhausted)
$canUse = DiscountHelper::canUse($discount);

// Check if discount is valid for cart value
$isValidForCart = DiscountHelper::isValidForCartValue($discount, 7500); // €75 in cents

// Check if discount is valid for current time (day/time restrictions)
$isValidForTime = DiscountHelper::isValidForCurrentTime($discount);
```

### Calculating Discounts

```php
// Calculate discount amount for a cart value
$cartValue = 10000; // €100 in cents
$discountAmount = DiscountHelper::calculateDiscountAmount($discount, $cartValue);
// Returns discount amount in cents
```

### Managing Discount Uses

```php
// Increment usage count
DiscountHelper::incrementUses($discount);

// Reset discount cache (useful after modifications)
DiscountHelper::resetCache();
```

### Managing Purchasables

```php
use App\Lunar\Models\ProductVariant;

$productVariant = ProductVariant::find(1);

// Add condition (product must be in cart)
DiscountHelper::addCondition($discount, $productVariant);

// Add reward (product gets discount)
DiscountHelper::addReward($discount, $productVariant);

// Add multiple conditions
DiscountHelper::addConditions($discount, [$variant1, $variant2]);

// Get conditions/rewards
$conditions = DiscountHelper::getConditions($discount);
$rewards = DiscountHelper::getRewards($discount);
```

### Deleting Discounts

```php
// Delete discount and all related data
DiscountHelper::delete($discount);
```

## Admin Panel Integration

All discount types are integrated with Lunar's admin panel, providing form fields for easy discount management.

### Available Fields

- **Percentage Discount**: Percentage, minimum cart value, maximum discount amount
- **Fixed Amount Discount**: Fixed amount, minimum cart value
- **BOGO Discount**: Buy quantity, get quantity, get discount percentage
- **Category Discount**: Discount type (percentage/fixed), discount value, minimum cart value
- **Product Discount**: Discount type (percentage/fixed), discount value, minimum cart value

## Applying Discounts to Carts

Discounts are automatically applied when a coupon code is entered. Use the existing `CartManager` service:

```php
use App\Services\CartManager;

$cartManager = app(CartManager::class);
$cartManager->applyDiscount('SAVE10'); // Apply coupon code
$cartManager->removeDiscount(); // Remove discount
```

## Best Practices

1. **Use Unique Handles**: Always use unique handles for discounts to avoid conflicts
2. **Set Priority**: Use priority to control which discounts apply first (higher = more priority)
3. **Use Max Uses**: Set `maxUses` to limit how many times a discount can be used
4. **Set Expiry Dates**: Always set `endsAt` for time-limited promotions
5. **Test Conditions**: Test discounts with various cart values and product combinations
6. **Use Stop Flag**: Set `stop(true)` if you want a discount to prevent other discounts from applying

## Troubleshooting

### Discount Not Applying

1. Check if discount is active: `DiscountHelper::isActive($discount)`
2. Check if discount can be used: `DiscountHelper::canUse($discount)`
3. Check minimum cart value: `DiscountHelper::isValidForCartValue($discount, $cartValue)`
4. Check time restrictions: `DiscountHelper::isValidForCurrentTime($discount)`
5. Reset discount cache: `DiscountHelper::resetCache()`

### Multiple Discounts Not Working

- Check discount priorities (higher priority applies first)
- Check if any discount has `stop` set to `true`
- Verify `maxUses` hasn't been exceeded

## API Reference

### DiscountService Methods

| Method | Description |
|--------|-------------|
| `percentageDiscount($name, $handle)` | Create a percentage discount builder |
| `fixedAmountDiscount($name, $handle)` | Create a fixed amount discount builder |
| `bogoDiscount($name, $handle)` | Create a BOGO discount builder |
| `categoryDiscount($name, $handle)` | Create a category discount builder |
| `productDiscount($name, $handle)` | Create a product discount builder |
| `percentage($percentage)` | Set discount percentage (0-100) |
| `fixedAmount($amount)` | Set fixed discount amount in cents |
| `minCartValue($amount)` | Set minimum cart value in cents |
| `maxDiscountAmount($amount)` | Set maximum discount cap in cents |
| `couponCode($code)` | Set coupon code |
| `startsAt($date)` | Set start date/time |
| `endsAt($date)` | Set end date/time |
| `maxUses($count)` | Set maximum number of uses |
| `priority($priority)` | Set priority (higher = more priority) |
| `stop($bool)` | Set whether to stop other discounts |
| `requireProducts($products)` | Add products that must be in cart |
| `applyToProducts($products)` | Add products that get discount |
| `requireCategories($categories)` | Add categories that must be in cart |
| `applyToCategories($categories)` | Add categories that get discount |
| `forCustomerGroups($groups)` | Limit to specific customer groups |
| `allowedDays($days)` | Set allowed days (0=Sunday, 6=Saturday) |
| `allowedTimeWindow($start, $end)` | Set allowed time window (HH:MM format) |
| `create()` | Create the discount |

---

**Happy discounting! 🎉**

