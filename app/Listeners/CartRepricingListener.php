<?php

namespace App\Listeners;

use App\Events\CartRepricingEvent;
use App\Services\CartPricing\RepricingTriggerService;

/**
 * Cart Repricing Listener - Handles repricing events.
 */
class CartRepricingListener
{
    public function __construct(
        protected RepricingTriggerService $triggerService
    ) {}

    /**
     * Handle cart repricing events.
     */
    public function handle(CartRepricingEvent $event): void
    {
        $this->triggerService->triggerReprice(
            $event->cart,
            $event->trigger,
            $event->context
        );
    }
}

