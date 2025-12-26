<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VariantLifecycleService;

/**
 * Process scheduled variant activations and deactivations.
 * 
 * Run this command via cron:
 * * * * * * php artisan variants:process-scheduled
 */
class ProcessScheduledVariantActivations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'variants:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled variant activations and deactivations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $service = app(VariantLifecycleService::class);

        $this->info('Processing scheduled activations...');
        $activated = $service->processScheduledActivations();
        $this->info("Activated {$activated} variants.");

        $this->info('Processing scheduled deactivations...');
        $deactivated = $service->processScheduledDeactivations();
        $this->info("Deactivated {$deactivated} variants.");

        return Command::SUCCESS;
    }
}


