<?php

namespace App\Listeners;

use App\Services\CartSessionService;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MergeCartOnLogin
{
    public function __construct(
        protected CartSessionService $cartSession
    ) {}

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // Only handle User logins, not Staff logins
        // Staff users don't have carts - they're admin users
        if (!($event->user instanceof \App\Models\User)) {
            return;
        }

        // Merge guest cart with user cart based on configuration
        $authPolicy = config('lunar.cart.auth_policy', 'merge');
        
        if ($authPolicy === 'merge') {
            $this->cartSession->mergeOnAuth($event->user);
        } else {
            // Override policy - just associate current cart with user
            $this->cartSession->associate($event->user);
        }
    }
}