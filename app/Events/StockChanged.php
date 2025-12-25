<?php

namespace App\Events;

use Lunar\Models\Cart;

/**
 * Stock changed event.
 */
class StockChanged extends CartRepricingEvent
{
    public function __construct(Cart $cart, array $context = [])
    {
        parent::__construct($cart, 'stock_changed', $context);
    }
}

