<?php

namespace App\Events;

use Lunar\Models\Cart;

/**
 * Cart variant changed event.
 */
class CartVariantChanged extends CartRepricingEvent
{
    public function __construct(Cart $cart, array $context = [])
    {
        parent::__construct($cart, 'variant_changed', $context);
    }
}

