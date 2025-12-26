<?php

namespace App\Events;

use Lunar\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an order is refunded.
 */
class OrderRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?float $refundAmount = null,
        public ?string $reason = null
    ) {}
}


