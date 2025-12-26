<?php

namespace App\Events;

use Lunar\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an order is paid.
 */
class OrderPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}
}


