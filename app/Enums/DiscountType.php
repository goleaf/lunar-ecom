<?php

namespace App\Enums;

use Osama\LaravelEnums\Concerns\EnumTranslatable;

/**
 * Discount Types
 * 
 * Defines the different types of discounts that can be applied
 * in the e-commerce system.
 */
enum DiscountType: string
{
    use EnumTranslatable;

    case ITEM_LEVEL = 'item_level';
    case CART_LEVEL = 'cart_level';
    case SHIPPING = 'shipping';
    case PAYMENT_METHOD = 'payment_method';
    case CUSTOMER_LOYALTY = 'customer_loyalty';
    case COUPON_BASED = 'coupon_based';
    case AUTOMATIC_PROMOTION = 'automatic_promotion';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return $this->trans();
    }

    /**
     * Get discount scope
     */
    public function scope(): string
    {
        return match($this) {
            self::ITEM_LEVEL => 'item',
            self::CART_LEVEL => 'cart',
            self::SHIPPING => 'shipping',
            self::PAYMENT_METHOD => 'payment',
            self::CUSTOMER_LOYALTY => 'cart',
            self::COUPON_BASED => 'cart',
            self::AUTOMATIC_PROMOTION => 'cart',
        };
    }
}


