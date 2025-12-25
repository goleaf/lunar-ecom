<?php

namespace App\Events;

use Lunar\Models\Cart;

/**
 * Cart customer changed event.
 */
class CartCustomerChanged extends CartRepricingEvent
{
    public function __construct(Cart $cart, array $context = [])
    {
        parent::__construct($cart, 'customer_changed', $context);
    }
}

