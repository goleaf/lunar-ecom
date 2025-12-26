<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Lunar\Models\Contracts\Cart;
use Lunar\DiscountTypes\AbstractDiscountType;

/**
 * Example custom discount type.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/extending/discounts
 * 
 * Extends AbstractDiscountType to create a custom discount that applies
 * a percentage to the cart subtotal.
 */
class CustomPercentageDiscount extends AbstractDiscountType
{
    /**
     * Return the name of the discount type.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Custom Percentage Discount';
    }

    /**
     * Called just before cart totals are calculated.
     * 
     * Apply the discount logic to the cart.
     *
     * @param Cart $cart
     * @return Cart
     */
    public function apply(Cart $cart): Cart
    {
        // Example: Apply 10% discount to the cart subtotal
        // This is a placeholder - implement according to your discount rules
        
        // Access discount data via $this->discount
        // Access discount purchasables via $this->discount->purchasables
        
        // Example implementation:
        // $percentage = $this->discount->data['percentage'] ?? 10;
        // $discountAmount = (int) ($cart->subTotal->value * ($percentage / 100));
        // 
        // Apply discount to cart
        // $cart->discount_total = new \Lunar\DataTypes\Price($discountAmount, $cart->currency, 1);
        
        return $cart;
    }
}
