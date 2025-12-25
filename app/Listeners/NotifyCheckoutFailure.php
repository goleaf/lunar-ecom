<?php

namespace App\Listeners;

use App\Events\CheckoutFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify administrators when checkout fails.
 */
class NotifyCheckoutFailure implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CheckoutFailed $event): void
    {
        $lock = $event->lock;
        $exception = $event->exception;

        // Only notify if configured
        if (!config('checkout.notifications.on_failure', false)) {
            return;
        }

        $notificationEmail = config('checkout.notifications.email');
        
        if (!$notificationEmail) {
            return;
        }

        // TODO: Create CheckoutFailureNotification
        // Mail::to($notificationEmail)->send(new CheckoutFailureNotification($lock, $exception));

        Log::warning('Checkout failure notification sent', [
            'lock_id' => $lock->id,
            'cart_id' => $lock->cart_id,
            'phase' => $lock->phase,
            'error' => $exception->getMessage(),
        ]);
    }
}

