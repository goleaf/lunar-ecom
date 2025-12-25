<?php

namespace App\Listeners\Cache;

use App\Services\Cache\CacheInvalidationService;
use Lunar\Models\Price;

/**
 * Listener for price updates - invalidates cache.
 */
class InvalidatePriceCache
{
    public function __construct(
        protected CacheInvalidationService $invalidationService
    ) {}

    /**
     * Handle price created event.
     */
    public function created(Price $price): void
    {
        $this->invalidationService->onPriceUpdate(
            $price->priceable_id,
            $price->currency_id,
            $price->customer_group_id
        );
    }

    /**
     * Handle price updated event.
     */
    public function updated(Price $price): void
    {
        $this->invalidationService->onPriceUpdate(
            $price->priceable_id,
            $price->currency_id,
            $price->customer_group_id
        );
    }

    /**
     * Handle price deleted event.
     */
    public function deleted(Price $price): void
    {
        $this->invalidationService->onPriceUpdate(
            $price->priceable_id,
            $price->currency_id,
            $price->customer_group_id
        );
    }
}

