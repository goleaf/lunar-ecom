<?php

namespace App\Listeners;

use App\Services\CartSessionService;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ClearCartOnLogout
{
    public function __construct(
        protected CartSessionService $cartSession
    ) {}

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        // Clear cart from session on logout if configured to do so
        if (config('lunar.cart_session.delete_on_forget', true)) {
            $this->cartSession->forget();
        }
    }
}