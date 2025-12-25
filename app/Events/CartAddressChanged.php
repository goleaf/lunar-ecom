<?php

namespace App\Events;

use Lunar\Models\Cart;

/**
 * Cart address changed event.
 */
class CartAddressChanged extends CartRepricingEvent
{
    public function __construct(Cart $cart, array $context = [])
    {
        parent::__construct($cart, 'address_changed', $context);
    }
}

