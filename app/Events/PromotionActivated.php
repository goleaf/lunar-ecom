<?php

namespace App\Events;

use Lunar\Models\Cart;

/**
 * Promotion activated event.
 */
class PromotionActivated extends CartRepricingEvent
{
    public function __construct(Cart $cart, array $context = [])
    {
        parent::__construct($cart, 'promotion_changed', $context);
    }
}

