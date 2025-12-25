<?php

namespace App\Jobs;

use App\Models\StockNotification;
use App\Models\StockNotificationMetric;
use App\Notifications\BackInStockNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Job to send back-in-stock notification email.
 */
class SendBackInStockNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public StockNotification $notification;

    /**
     * Create a new job instance.
     */
    public function __construct(StockNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ensure notification is still active
        $this->notification->refresh();
        if (!$this->notification->is_active) {
            return;
        }

        $variant = $this->notification->productVariant;
        if (!$variant) {
            Log::warning("Product variant not found for notification {$this->notification->id}");
            return;
        }

        // Create or get metrics record
        $metrics = StockNotificationMetric::firstOrCreate(
            [
                'stock_notification_id' => $this->notification->id,
                'product_variant_id' => $variant->id,
            ],
            [
                'email_sent' => false,
            ]
        );

        try {
            // Send notification
            Notification::route('mail', $this->notification->customer_email)
                ->notify(new BackInStockNotification($this->notification, $variant));

            // Mark metrics
            $metrics->markEmailSent();
            
            Log::info("Back-in-stock notification sent to {$this->notification->customer_email} for variant {$variant->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send back-in-stock notification {$this->notification->id}: " . $e->getMessage());
            throw $e; // Re-throw to retry the job
        }
    }
}

