<?php

namespace App\Events;

use Lunar\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a chargeback is received for an order.
 */
class ChargebackReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $chargebackId = null,
        public ?string $reason = null,
        public ?float $amount = null
    ) {}
}


