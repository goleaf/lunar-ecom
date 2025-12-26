<?php

namespace App\Jobs;

use App\Services\ProductSchedulingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to process product schedules (runs every hour).
 */
class ProcessProductSchedules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(ProductSchedulingService $schedulingService): void
    {
        try {
            // Execute due schedules
            $executed = $schedulingService->executeDueSchedules();
            
            // Handle expired schedules
            $expired = $schedulingService->handleExpiredSchedules();
            
            // Process seasonal products
            $seasonalService = app(\App\Services\SeasonalProductService::class);
            $seasonalService->processSeasonalProducts();
            
            // Send scheduled notifications
            $schedulingService->sendScheduledNotifications();
            
            Log::info("Processed product schedules: {$executed} executed, {$expired} expired");
        } catch (\Exception $e) {
            Log::error('Failed to process product schedules: ' . $e->getMessage());
            throw $e;
        }
    }
}


