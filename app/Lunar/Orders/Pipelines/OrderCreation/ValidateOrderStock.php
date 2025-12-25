<?php

namespace App\Lunar\Orders\Pipelines\OrderCreation;

use App\Services\StockService;
use Closure;
use Lunar\Models\Order;

/**
 * Order pipeline hook for validating stock before order creation.
 * 
 * This validates stock and confirms reservations when order is placed.
 */
class ValidateOrderStock
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Handle the incoming order.
     */
    public function handle(Order $order, Closure $next): Order
    {
        $sessionId = session()->getId();
        $userId = $order->user_id;

        foreach ($order->lines as $line) {
            $purchasable = $line->purchasable;
            
            // Only validate ProductVariant stock
            if (!$purchasable instanceof \Lunar\Models\ProductVariant) {
                continue;
            }

            $variant = $purchasable;
            $quantity = $line->quantity;

            // Find and confirm reservation
            $reservation = \App\Models\StockReservation::where('product_variant_id', $variant->id)
                ->where(function ($q) use ($sessionId, $userId) {
                    $q->where('session_id', $sessionId)
                      ->orWhere('user_id', $userId);
                })
                ->where('is_released', false)
                ->where('expires_at', '>', now())
                ->where('quantity', $quantity)
                ->first();

            if ($reservation) {
                // Confirm reservation (convert to sale)
                $this->stockService->confirmReservation($reservation, $order);
            } else {
                // No reservation found, check availability and reserve
                $totalAvailable = $this->stockService->getTotalAvailableStock($variant);
                
                if ($variant->purchasable === 'in_stock' && $totalAvailable < $quantity) {
                    throw new \Exception("Insufficient stock for {$variant->sku}. Only {$totalAvailable} available.");
                }

                // Reserve and confirm immediately
                $newReservation = $this->stockService->reserveStock(
                    $variant,
                    $quantity,
                    $sessionId,
                    $userId,
                    null,
                    5
                );

                if ($newReservation) {
                    $this->stockService->confirmReservation($newReservation, $order);
                } else {
                    throw new \Exception("Unable to reserve stock for {$variant->sku}.");
                }
            }
        }

        return $next($order);
    }
}


