<?php

namespace App\Events;

use Lunar\Models\Cart;

/**
 * Promotion expired event.
 */
class PromotionExpired extends CartRepricingEvent
{
    public function __construct(Cart $cart, array $context = [])
    {
        parent::__construct($cart, 'promotion_changed', $context);
    }
}

