<?php

namespace App\Events;

use App\Models\CheckoutLock;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Lunar\Models\Order;

/**
 * Event fired when checkout is completed successfully.
 */
class CheckoutCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CheckoutLock $lock,
        public readonly Order $order
    ) {}
}

