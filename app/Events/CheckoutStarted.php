<?php

namespace App\Events;

use App\Models\CheckoutLock;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when checkout is started.
 */
class CheckoutStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CheckoutLock $lock
    ) {}
}

