<?php

namespace App\Listeners\Cache;

use App\Events\PromotionActivated;
use App\Events\PromotionExpired;
use App\Services\Cache\CacheInvalidationService;
use Lunar\Models\Discount;

/**
 * Listener for promotion updates - invalidates cache.
 */
class InvalidatePromotionCache
{
    public function __construct(
        protected CacheInvalidationService $invalidationService
    ) {}

    /**
     * Handle promotion activated event.
     */
    public function handlePromotionActivated(PromotionActivated $event): void
    {
        $this->invalidationService->onPromotionUpdate($event->promotion->id ?? null);
    }

    /**
     * Handle promotion expired event.
     */
    public function handlePromotionExpired(PromotionExpired $event): void
    {
        $this->invalidationService->onPromotionUpdate($event->promotion->id ?? null);
    }

    /**
     * Handle discount created event.
     */
    public function created(Discount $discount): void
    {
        $this->invalidationService->onPromotionUpdate($discount->id);
    }

    /**
     * Handle discount updated event.
     */
    public function updated(Discount $discount): void
    {
        $this->invalidationService->onPromotionUpdate($discount->id);
    }

    /**
     * Handle discount deleted event.
     */
    public function deleted(Discount $discount): void
    {
        $this->invalidationService->onPromotionUpdate($discount->id);
    }
}

