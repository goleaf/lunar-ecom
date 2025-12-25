<?php

namespace App\Listeners\Cache;

use App\Events\StockChanged;
use App\Services\Cache\CacheInvalidationService;
use Lunar\Models\ProductVariant;

/**
 * Listener for stock updates - invalidates cache.
 */
class InvalidateStockCache
{
    public function __construct(
        protected CacheInvalidationService $invalidationService
    ) {}

    /**
     * Handle stock changed event.
     */
    public function handle(StockChanged $event): void
    {
        $this->invalidationService->onStockUpdate($event->variantId);
    }

    /**
     * Handle variant updated event (may affect stock).
     */
    public function updated(ProductVariant $variant): void
    {
        if ($variant->isDirty(['stock', 'backorder', 'purchasable'])) {
            $this->invalidationService->onStockUpdate($variant->id);
        }
    }
}

