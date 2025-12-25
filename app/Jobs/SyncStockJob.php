<?php

namespace App\Jobs;

use App\Services\InventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lunar\Models\ProductVariant;

/**
 * Async job for stock synchronization.
 * 
 * Used for:
 * - Multi-warehouse stock sync
 * - Stock reservation cleanup
 * - Low stock alerts
 */
class SyncStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        public ?int $variantId = null,
        public ?int $warehouseId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(InventoryService $inventoryService): void
    {
        try {
            $startTime = microtime(true);

            if ($this->variantId) {
                // Sync specific variant
                $variant = ProductVariant::find($this->variantId);
                if ($variant) {
                    $this->syncVariantStock($variant, $inventoryService);
                }
            } else {
                // Sync all variants (batch)
                $this->syncAllStock($inventoryService);
            }

            $duration = (microtime(true) - $startTime) * 1000;

            Log::info('SyncStockJob: Completed', [
                'variant_id' => $this->variantId,
                'warehouse_id' => $this->warehouseId,
                'duration_ms' => round($duration, 2),
            ]);
        } catch (\Exception $e) {
            Log::error('SyncStockJob: Failed', [
                'variant_id' => $this->variantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync stock for a specific variant.
     */
    protected function syncVariantStock(ProductVariant $variant, InventoryService $inventoryService): void
    {
        // Update variant stock from inventory levels
        $availability = $inventoryService->checkAvailability($variant, 1, $this->warehouseId);
        $totalStock = $availability['total_available'] ?? 0;
        $variant->update(['stock' => $totalStock]);
    }

    /**
     * Sync all stock (batch operation).
     */
    protected function syncAllStock(InventoryService $inventoryService): void
    {
        // Batch process variants in chunks
        ProductVariant::chunk(100, function ($variants) use ($inventoryService) {
            foreach ($variants as $variant) {
                $this->syncVariantStock($variant, $inventoryService);
            }
        });
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncStockJob: Permanently failed', [
            'variant_id' => $this->variantId,
            'error' => $exception->getMessage(),
        ]);
    }
}

