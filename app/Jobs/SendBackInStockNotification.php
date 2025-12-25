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
        // Ensure notification is still pending
        $this->notification->refresh();
        if ($this->notification->status !== 'pending') {
            return;
        }

        $variant = $this->notification->productVariant ?? $this->notification->getVariant();
        if (!$variant) {
            Log::warning("Product variant not found for notification {$this->notification->id}");
            return;
        }

        // Create or get metrics record (create before sending so we have the ID)
        $metrics = StockNotificationMetric::firstOrNew([
            'stock_notification_id' => $this->notification->id,
            'product_variant_id' => $variant->id,
        ]);
        
        if (!$metrics->exists) {
            $metrics->email_sent = false;
            $metrics->save();
        }

        try {
            // Send notification (pass metrics ID to notification)
            Notification::route('mail', $this->notification->email)
                ->notify(new BackInStockNotification($this->notification, $variant, $metrics));

            // Mark metrics and notification as sent
            $metrics->markEmailSent();
            $this->notification->markAsNotified();
            
            Log::info("Back-in-stock notification sent to {$this->notification->email} for variant {$variant->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send back-in-stock notification {$this->notification->id}: " . $e->getMessage());
            throw $e; // Re-throw to retry the job
        }
    }
}

