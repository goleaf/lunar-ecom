<?php

namespace App\Observers;

use App\Models\InventoryLevel;
use App\Services\StockNotificationService;

/**
 * Observer for InventoryLevel model to trigger back-in-stock notifications.
 */
class InventoryLevelObserver
{
    public function __construct(
        protected StockNotificationService $notificationService
    ) {}

    /**
     * Handle the InventoryLevel "updated" event.
     *
     * @param  InventoryLevel  $level
     * @return void
     */
    public function updated(InventoryLevel $level): void
    {
        // Check if stock went from 0 (or below threshold) to available
        $wasOutOfStock = $level->getOriginal('quantity') <= 0;
        $isNowInStock = $level->quantity > 0;

        // Only trigger if stock became available
        if ($wasOutOfStock && $isNowInStock) {
            // Process notifications for this variant
            $this->notificationService->processQueue($level->productVariant);
        }
    }
}

