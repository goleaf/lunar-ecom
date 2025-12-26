<?php

namespace App\Listeners\Cache;

use App\Events\ContractValidityChanged;
use App\Services\Cache\CacheInvalidationService;

/**
 * Listener for contract updates - invalidates cache.
 */
class InvalidateContractCache
{
    public function __construct(
        protected CacheInvalidationService $invalidationService
    ) {}

    /**
     * Handle contract validity changed event.
     */
    public function handle(ContractValidityChanged $event): void
    {
        $this->invalidationService->onContractUpdate(
            $event->customerId ?? 0,
            $event->variantId ?? null
        );
    }
}


