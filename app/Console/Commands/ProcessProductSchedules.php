<?php

namespace App\Console\Commands;

use App\Jobs\ProcessProductSchedules as ProcessProductSchedulesJob;
use Illuminate\Console\Command;

/**
 * Artisan command to process product schedules.
 */
class ProcessProductSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:process-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due product schedules and handle expired schedules';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Processing product schedules...');

        // Dispatch the job to queue
        ProcessProductSchedulesJob::dispatch();

        $this->info('Product schedules processing job dispatched.');
    }
}
