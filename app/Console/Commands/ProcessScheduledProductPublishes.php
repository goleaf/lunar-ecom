<?php

namespace App\Console\Commands;

use App\Services\ProductCoreService;
use Illuminate\Console\Command;

/**
 * Command to process scheduled product publish/unpublish actions.
 * 
 * This should be run via cron or scheduler:
 * 
 * In app/Console/Kernel.php:
 * $schedule->command('products:process-scheduled-publishes')->everyMinute();
 */
class ProcessScheduledProductPublishes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:process-scheduled-publishes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled product publish and unpublish actions';

    /**
     * Execute the console command.
     */
    public function handle(ProductCoreService $service): int
    {
        $this->info('Processing scheduled product publishes...');

        $count = $service->processScheduledPublishes();

        if ($count > 0) {
            $this->info("Processed {$count} product(s).");
        } else {
            $this->info('No products scheduled for publish/unpublish.');
        }

        return Command::SUCCESS;
    }
}

