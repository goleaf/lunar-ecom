<?php

namespace App\Listeners\Cache;

use App\Services\Cache\CacheInvalidationService;
use Lunar\Models\Currency;

/**
 * Listener for currency rate updates - invalidates cache.
 */
class InvalidateCurrencyCache
{
    public function __construct(
        protected CacheInvalidationService $invalidationService
    ) {}

    /**
     * Handle currency updated event.
     */
    public function updated(Currency $currency): void
    {
        if ($currency->isDirty(['exchange_rate', 'default'])) {
            $this->invalidationService->onCurrencyRateUpdate($currency->id);
        }
    }
}


