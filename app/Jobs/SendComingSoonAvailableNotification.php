<?php

namespace App\Jobs;

use App\Models\ComingSoonNotification;
use App\Notifications\ProductComingSoonAvailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Job to notify customers when a coming soon product becomes available.
 */
class SendComingSoonAvailableNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ComingSoonNotification $notification;

    /**
     * Create a new job instance.
     */
    public function __construct(ComingSoonNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Notification::route('mail', $this->notification->email)
            ->notify(new ProductComingSoonAvailable($this->notification));
    }
}


