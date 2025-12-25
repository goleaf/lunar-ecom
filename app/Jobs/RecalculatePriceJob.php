<?php

namespace App\Jobs;

use App\Services\CartPricingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Cart;

/**
 * Async job for price recalculation.
 * 
 * Used for:
 * - Bulk price updates
 * - Background repricing after promotions
 * - Scheduled price refreshes
 */
class RecalculatePriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $cartId,
        public ?string $trigger = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CartPricingEngine $pricingEngine): void
    {
        $cart = Cart::find($this->cartId);
        
        if (!$cart) {
            Log::warning('RecalculatePriceJob: Cart not found', ['cart_id' => $this->cartId]);
            return;
        }

        try {
            $startTime = microtime(true);
            
            $pricingEngine->repriceCart($cart, $this->trigger ?? 'async_recalc');
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('RecalculatePriceJob: Completed', [
                'cart_id' => $this->cartId,
                'trigger' => $this->trigger,
                'duration_ms' => round($duration, 2),
            ]);
        } catch (\Exception $e) {
            Log::error('RecalculatePriceJob: Failed', [
                'cart_id' => $this->cartId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RecalculatePriceJob: Permanently failed', [
            'cart_id' => $this->cartId,
            'error' => $exception->getMessage(),
        ]);
    }
}

