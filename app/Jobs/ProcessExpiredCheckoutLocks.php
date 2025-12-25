<?php

namespace App\Jobs;

use App\Services\CheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to process expired checkout locks asynchronously.
 */
class ProcessExpiredCheckoutLocks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CheckoutService $checkoutService): void
    {
        try {
            $count = $checkoutService->cleanupExpiredLocks();
            
            Log::info('Expired checkout locks processed', [
                'count' => $count,
                'processed_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process expired checkout locks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessExpiredCheckoutLocks job failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }
}

