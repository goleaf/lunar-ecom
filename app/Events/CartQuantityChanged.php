<?php

namespace App\Events;

use Lunar\Models\Cart;

/**
 * Cart quantity changed event.
 */
class CartQuantityChanged extends CartRepricingEvent
{
    public function __construct(Cart $cart, array $context = [])
    {
        parent::__construct($cart, 'quantity_changed', $context);
    }
}

