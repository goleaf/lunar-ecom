<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Lunar\Models\Cart;

/**
 * Base cart repricing event.
 */
abstract class CartRepricingEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Cart $cart,
        public readonly string $trigger,
        public readonly array $context = []
    ) {}
}

