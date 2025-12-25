<?php

namespace App\Events;

use Lunar\Models\Cart;

/**
 * Cart currency changed event.
 */
class CartCurrencyChanged extends CartRepricingEvent
{
    public function __construct(Cart $cart, array $context = [])
    {
        parent::__construct($cart, 'currency_changed', $context);
    }
}

