<?php

namespace App\Observers;

use Lunar\Models\Order;
use App\Services\AbandonedCartService;

/**
 * Observer for Order model to track conversions.
 */
class OrderObserver
{
    /**
     * Handle the Order "created" event.
     *
     * @param  Order  $order
     * @return void
     */
    public function created(Order $order): void
    {
        if ($order->cart_id) {
            $service = app(AbandonedCartService::class);
            $service->markAsConverted($order->cart);
        }
    }
}

