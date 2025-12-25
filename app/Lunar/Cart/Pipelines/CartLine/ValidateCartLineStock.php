<?php

namespace App\Lunar\Cart\Pipelines\CartLine;

use App\Services\StockService;
use Closure;
use Lunar\Models\CartLine;

/**
 * Cart pipeline hook for validating cart line stock.
 * 
 * This validates stock availability and reserves stock during checkout.
 */
class ValidateCartLineStock
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Handle the incoming cart line.
     */
    public function handle(CartLine $cartLine, Closure $next): CartLine
    {
        $purchasable = $cartLine->purchasable;
        
        // Only validate ProductVariant stock
        if (!$purchasable instanceof \Lunar\Models\ProductVariant) {
            return $next($cartLine);
        }

        $variant = $purchasable;
        $quantity = $cartLine->quantity;

        // Check if variant is purchasable
        if ($variant->purchasable === 'never') {
            throw new \Exception("Product variant is not available for purchase.");
        }

        // Check stock availability
        $totalAvailable = $this->stockService->getTotalAvailableStock($variant);
        
        if ($variant->purchasable === 'in_stock' && $totalAvailable < $quantity) {
            throw new \Exception("Insufficient stock. Only {$totalAvailable} available.");
        }

        // For 'always' purchasable, check stock + backorder
        if ($variant->purchasable === 'always') {
            $totalWithBackorder = $totalAvailable + ($variant->backorder ?? 0);
            if ($totalWithBackorder < $quantity) {
                throw new \Exception("Insufficient stock. Only {$totalWithBackorder} available (including backorder).");
            }
        }

        // Reserve stock if in checkout
        $sessionId = session()->getId();
        $userId = auth()->id();
        
        // Only reserve if not already reserved
        $existingReservation = \App\Models\StockReservation::where('product_variant_id', $variant->id)
            ->where('session_id', $sessionId)
            ->where('is_released', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$existingReservation) {
            $reservation = $this->stockService->reserveStock(
                $variant,
                $quantity,
                $sessionId,
                $userId,
                null,
                15 // 15 minutes expiry
            );

            if (!$reservation) {
                throw new \Exception("Unable to reserve stock. Please try again.");
            }
        }

        return $next($cartLine);
    }
}


