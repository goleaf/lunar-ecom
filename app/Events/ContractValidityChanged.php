<?php

namespace App\Events;

use Lunar\Models\Cart;

/**
 * Contract validity changed event.
 */
class ContractValidityChanged extends CartRepricingEvent
{
    public function __construct(Cart $cart, array $context = [])
    {
        parent::__construct($cart, 'contract_changed', $context);
    }
}

