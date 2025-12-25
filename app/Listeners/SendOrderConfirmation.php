<?php

namespace App\Listeners;

use App\Events\CheckoutCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Send order confirmation email when checkout completes.
 */
class SendOrderConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CheckoutCompleted $event): void
    {
        $order = $event->order;
        
        // Only send if order has a user with email
        if (!$order->user || !$order->user->email) {
            return;
        }

        // TODO: Create OrderConfirmation mailable
        // Mail::to($order->user->email)->send(new OrderConfirmation($order));
        
        \Log::info('Order confirmation email queued', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
        ]);
    }
}

