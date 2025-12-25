# 🎁 Discount System Quick Start Guide

## Quick Examples

### 10% Off Orders Over €50
```php
use App\Services\DiscountService;

$discount = DiscountService::percentageDiscount('10% Off Over €50', '10_percent_over_50')
    ->percentage(10)
    ->minCartValue(5000) // €50 in cents
    ->couponCode('SAVE10')
    ->create();
```

### €15 Fixed Discount
```php
$discount = DiscountService::fixedAmountDiscount('€15 Off', '15_off')
    ->fixedAmount(1500) // €15 in cents
    ->minCartValue(10000) // €100 minimum
    ->couponCode('SAVE15')
    ->create();
```

### Buy One Get One Free
```php
use App\Lunar\Models\ProductVariant;

$product = ProductVariant::find(1);

$discount = DiscountService::bogoDiscount('BOGO Sale', 'bogo')
    ->requireProducts([$product])
    ->withData([
        'buy_quantity' => 1,
        'get_quantity' => 1,
        'get_discount' => 100, // 100% = free
    ])
    ->couponCode('BOGO')
    ->create();
```

## Using Discounts

1. **Create a discount** using `DiscountService`
2. **Apply to cart** using the coupon code via `CartManager`
3. **Discounts are automatically applied** during cart calculation

## Files Created

- `app/Services/DiscountService.php` - Builder service for creating discounts
- `app/Lunar/Discounts/DiscountTypes/PercentageDiscount.php` - Percentage discount type
- `app/Lunar/Discounts/DiscountTypes/FixedAmountDiscount.php` - Fixed amount discount type
- `app/Lunar/Discounts/DiscountTypes/BOGODiscount.php` - BOGO discount type
- `app/Lunar/Discounts/DiscountTypes/CategoryDiscount.php` - Category discount type
- `app/Lunar/Discounts/DiscountTypes/ProductDiscount.php` - Product discount type
- `app/Lunar/Discounts/DiscountHelper.php` - Enhanced with additional utility methods

## Documentation

See `ADVANCED_DISCOUNTS.md` for complete documentation and more examples.

