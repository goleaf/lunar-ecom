<?php

namespace App\Listeners;

use App\Services\DigitalProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Lunar\Events\OrderStatusChanged;

/**
 * Listener to deliver digital products when order is completed.
 */
class DeliverDigitalProducts implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @param  DigitalProductService  $digitalProductService
     */
    public function __construct(
        protected DigitalProductService $digitalProductService
    ) {}

    /**
     * Handle the event.
     *
     * @param  OrderStatusChanged  $event
     * @return void
     */
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        // Only deliver when order is placed (paid/completed)
        // Lunar uses 'placed_at' to indicate order completion
        if ($order->placed_at && $order->placed_at->isPast()) {
            // Check if there are digital products that need delivery
            $hasDigitalProducts = false;
            foreach ($order->lines as $orderLine) {
                $purchasable = $orderLine->purchasable;
                if ($purchasable instanceof \Lunar\Models\ProductVariant) {
                    $digitalProduct = \App\Models\DigitalProduct::where('product_variant_id', $purchasable->id)
                        ->where('is_digital', true)
                        ->where('auto_deliver', true)
                        ->first();
                    
                    if ($digitalProduct && $digitalProduct->hasFile()) {
                        // Check if already delivered
                        $existing = \App\Models\DownloadLink::where('order_id', $order->id)
                            ->where('order_line_id', $orderLine->id)
                            ->where('product_variant_id', $purchasable->id)
                            ->exists();
                        
                        if (!$existing) {
                            $hasDigitalProducts = true;
                            break;
                        }
                    }
                }
            }

            if ($hasDigitalProducts) {
                $this->digitalProductService->deliverOrderDigitalProducts($order);
            }
        }
    }
}

