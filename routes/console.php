<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process scheduled product publish/unpublish actions every minute
Schedule::command('products:process-scheduled-publishes')->everyMinute();

// Process scheduled collections and auto-publish/unpublish products every minute
Schedule::command('collections:process-scheduled')->everyMinute();

// Check stock notifications every hour
Schedule::command('stock:check-notifications')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Process product schedules every minute
Schedule::command('products:process-schedules')->everyMinute();

// Process product badges daily
Schedule::command('products:process-badges')->daily();

// Process collection assignments hourly
Schedule::command('collections:process-assignments')->hourly();

// Cleanup expired checkout locks every 5 minutes
// Option 1: Use command directly
Schedule::command('checkout:cleanup-expired-locks')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Option 2: Use queue job (uncomment to use queue instead)
// Schedule::job(new \App\Jobs\ProcessExpiredCheckoutLocks())
//     ->everyFiveMinutes()
//     ->withoutOverlapping();
