<?php

namespace App\Jobs;

use App\Models\DigitalProduct;
use App\Models\DigitalProductVersion;
use App\Notifications\DigitalProductUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Job to notify customers about digital product updates.
 */
class NotifyDigitalProductUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public DigitalProduct $digitalProduct;
    public DigitalProductVersion $version;

    /**
     * Create a new job instance.
     */
    public function __construct(DigitalProduct $digitalProduct, DigitalProductVersion $version)
    {
        $this->digitalProduct = $digitalProduct;
        $this->version = $version;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all customers who have purchased this digital product
        $downloads = \App\Models\Download::where('digital_product_id', $this->digitalProduct->id)
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['paid', 'completed', 'shipped']);
            })
            ->with('customer')
            ->get();

        foreach ($downloads as $download) {
            if ($download->customer) {
                $download->customer->notify(new DigitalProductUpdated($this->digitalProduct, $this->version, $download));
            } else {
                // Send to email from order
                $email = $download->order->billingAddress?->contact_email 
                    ?? $download->order->customer?->email 
                    ?? null;

                if ($email) {
                    Notification::route('mail', $email)
                        ->notify(new DigitalProductUpdated($this->digitalProduct, $this->version, $download));
                }
            }
        }
    }
}


