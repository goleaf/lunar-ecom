<?php

namespace App\Jobs;

use App\Models\ProductSchedule;
use App\Notifications\ProductScheduleNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Job to send schedule notifications to admins.
 */
class SendScheduleNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ProductSchedule $schedule;

    /**
     * Create a new job instance.
     */
    public function __construct(ProductSchedule $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get admin users
        $admins = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new ProductScheduleNotification($this->schedule));
    }
}

