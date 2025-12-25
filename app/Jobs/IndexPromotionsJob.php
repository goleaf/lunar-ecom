<?php

namespace App\Jobs;

use App\Services\Cache\PricingCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Discount;

/**
 * Async job for promotion indexing.
 * 
 * Used for:
 * - Rebuilding promotion cache
 * - Indexing active promotions
 * - Updating promotion availability
 */
class IndexPromotionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public ?int $channelId = null,
        public ?int $customerGroupId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PricingCacheService $cacheService): void
    {
        try {
            $startTime = microtime(true);

            // Rebuild promotion cache
            $promotions = $cacheService->getPromotionDefinitions(
                $this->channelId,
                $this->customerGroupId
            );

            $duration = (microtime(true) - $startTime) * 1000;

            Log::info('IndexPromotionsJob: Completed', [
                'channel_id' => $this->channelId,
                'customer_group_id' => $this->customerGroupId,
                'promotions_count' => $promotions->count(),
                'duration_ms' => round($duration, 2),
            ]);
        } catch (\Exception $e) {
            Log::error('IndexPromotionsJob: Failed', [
                'channel_id' => $this->channelId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('IndexPromotionsJob: Permanently failed', [
            'channel_id' => $this->channelId,
            'error' => $exception->getMessage(),
        ]);
    }
}

