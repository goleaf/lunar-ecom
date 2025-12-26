<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Product;

/**
 * Async job for search reindexing.
 * 
 * Used for:
 * - Product search index updates
 * - Price changes in search
 * - Stock availability in search
 */
class ReindexSearchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        public ?int $productId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $startTime = microtime(true);

            if ($this->productId) {
                // Reindex specific product
                $product = Product::find($this->productId);
                if ($product) {
                    $product->searchable();
                }
            } else {
                // Reindex all products (batch)
                Product::chunk(100, function ($products) {
                    foreach ($products as $product) {
                        $product->searchable();
                    }
                });
            }

            $duration = (microtime(true) - $startTime) * 1000;

            Log::info('ReindexSearchJob: Completed', [
                'product_id' => $this->productId,
                'duration_ms' => round($duration, 2),
            ]);
        } catch (\Exception $e) {
            Log::error('ReindexSearchJob: Failed', [
                'product_id' => $this->productId,
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
        Log::error('ReindexSearchJob: Permanently failed', [
            'product_id' => $this->productId,
            'error' => $exception->getMessage(),
        ]);
    }
}


