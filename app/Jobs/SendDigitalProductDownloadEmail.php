<?php

namespace App\Jobs;

use App\Models\Download;
use App\Notifications\DigitalProductDownloadAvailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Job to send digital product download email.
 */
class SendDigitalProductDownloadEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Download $download;

    /**
     * Create a new job instance.
     */
    public function __construct(Download $download)
    {
        $this->download = $download;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $customer = $this->download->customer;
        $order = $this->download->order;

        if (!$customer) {
            // Try to get email from order
            $email = $order->billingAddress?->contact_email 
                ?? $order->customer?->email 
                ?? null;

            if (!$email) {
                Log::warning("No email found for download {$this->download->id}");
                return;
            }

            // Send to email address
            Notification::route('mail', $email)
                ->notify(new DigitalProductDownloadAvailable($this->download));
        } else {
            // Send to customer
            $customer->notify(new DigitalProductDownloadAvailable($this->download));
        }

        // Mark license key as sent if applicable
        if ($this->download->license_key && !$this->download->license_key_sent) {
            $this->download->update(['license_key_sent' => true]);
        }
    }
}

