<?php

namespace App\Jobs;

use App\Models\LowStockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Job to send low stock alert notifications.
 */
class SendLowStockAlertNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public LowStockAlert $alert
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip if already sent or resolved
        if ($this->alert->notification_sent || $this->alert->is_resolved) {
            return;
        }

        try {
            // Get warehouse email or default admin email
            $warehouse = $this->alert->warehouse;
            $email = $warehouse->email ?? config('mail.from.address');
            
            if (!$email) {
                Log::warning("No email address found for low stock alert", [
                    'alert_id' => $this->alert->id,
                    'warehouse_id' => $this->alert->warehouse_id,
                ]);
                return;
            }

            // Send email notification
            Mail::send('emails.low-stock-alert', [
                'alert' => $this->alert,
                'product' => $this->alert->productVariant->product,
                'variant' => $this->alert->productVariant,
                'warehouse' => $warehouse,
            ], function ($message) use ($email, $warehouse) {
                $message->to($email)
                    ->subject("Low Stock Alert: {$this->alert->productVariant->product->translateAttribute('name')} - {$warehouse->name}");
            });

            // Mark as sent
            $this->alert->update([
                'notification_sent' => true,
                'notification_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send low stock alert notification", [
                'alert_id' => $this->alert->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
