<?php

namespace App\Listeners;

use App\Models\StockNotificationMetric;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Lunar\Events\OrderStatusChanged;

/**
 * Listener to track conversions from stock notification emails.
 */
class TrackStockNotificationConversion implements ShouldQueue
{
    use InteractsWithQueue;

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

        // Get customer email
        $customerEmail = $order->billingAddress?->contact_email 
            ?? $order->customer?->email 
            ?? $order->customer?->contact_email
            ?? null;
        
        if (!$customerEmail) {
            return;
        }

        // Track conversions for all products in the order
        foreach ($order->lines as $line) {
            $purchasable = $line->purchasable;
            
            if ($purchasable instanceof \Lunar\Models\ProductVariant) {
                // Find metrics for this variant and email
                $notification = \App\Models\StockNotification::where('product_variant_id', $purchasable->id)
                    ->where('email', $customerEmail)
                    ->where('status', 'sent')
                    ->first();
                
                if ($notification) {
                    $metric = StockNotificationMetric::where('stock_notification_id', $notification->id)
                        ->where('product_variant_id', $purchasable->id)
                        ->first();
                    
                    if ($metric && !$metric->converted) {
                        $metric->markAsConverted($order->id);
                    }
                }
            }
        }
    }
}

