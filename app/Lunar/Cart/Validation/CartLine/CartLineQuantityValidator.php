<?php

namespace App\Lunar\Cart\Validation\CartLine;

use Lunar\Validation\BaseValidator;

/**
 * Example cart line quantity validator.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/extending/carts#action-validation
 * 
 * Validators allow you to add custom validation logic for cart actions
 * (e.g., adding items to cart, updating quantities, etc.).
 */
class CartLineQuantityValidator extends BaseValidator
{
    /**
     * Validate the cart line quantity.
     *
     * @return bool
     */
    public function validate(): bool
    {
        $quantity = $this->parameters['quantity'] ?? 0;

        // Example validation: Ensure quantity is positive
        if ($quantity <= 0) {
            return $this->fail('cart', 'Quantity must be greater than zero');
        }

        // Example validation: Check maximum quantity
        $maxQuantity = $this->parameters['max_quantity'] ?? 999;
        if ($quantity > $maxQuantity) {
            return $this->fail('cart', "Quantity cannot exceed {$maxQuantity}");
        }

        // Example validation: Check stock availability
        // $purchasable = $this->parameters['purchasable'] ?? null;
        // if ($purchasable && $quantity > $purchasable->stock) {
        //     return $this->fail('cart', 'Insufficient stock available');
        // }

        return $this->pass();
    }
}


