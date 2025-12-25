<?php

namespace App\Listeners;

use App\Services\DigitalProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Lunar\Events\OrderStatusChanged;

/**
 * Listener to create download links when order is paid.
 */
class CreateDigitalProductDownloads implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected DigitalProductService $digitalProductService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        
        // Only process if order is paid/completed
        if (!in_array($order->status, ['paid', 'completed', 'shipped'])) {
            return;
        }

        // Create downloads for all digital products in the order
        $downloads = $this->digitalProductService->createDownloadsForOrder($order);

        // Send email notifications for each download
        foreach ($downloads as $download) {
            \App\Jobs\SendDigitalProductDownloadEmail::dispatch($download);
        }
    }
}

