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
