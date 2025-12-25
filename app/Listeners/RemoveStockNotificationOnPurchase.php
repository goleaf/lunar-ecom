<?php

namespace App\Listeners;

use App\Services\StockNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Lunar\Events\OrderStatusChanged;

/**
 * Listener to remove stock notifications when customer purchases the product.
 */
class RemoveStockNotificationOnPurchase implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected StockNotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        
        // Only process if order is completed/paid
        if (!in_array($order->status, ['completed', 'paid', 'shipped'])) {
            return;
        }

        // Remove notifications for all products in the order
        foreach ($order->lines as $line) {
            $purchasable = $line->purchasable;
            
            if ($purchasable instanceof \Lunar\Models\ProductVariant) {
                // Try to get customer email from order
                $customerEmail = $order->billingAddress?->contact_email 
                    ?? $order->customer?->email 
                    ?? $order->customer?->contact_email
                    ?? null;
                
                if ($customerEmail) {
                    $this->notificationService->removeAfterPurchase(
                        $purchasable->id,
                        $customerEmail
                    );
                }
            }
        }
    }
}

