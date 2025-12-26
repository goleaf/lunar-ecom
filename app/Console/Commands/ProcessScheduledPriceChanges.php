<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvancedPricingService;

/**
 * Process scheduled price changes.
 * 
 * Run via cron:
 * * * * * * php artisan pricing:process-scheduled-changes
 */
class ProcessScheduledPriceChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pricing:process-scheduled-changes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled price changes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $service = app(AdvancedPricingService::class);
        
        $this->info('Processing scheduled price changes...');
        
        $updated = $service->processScheduledPriceChanges();
        
        $this->info("Updated {$updated} prices.");
        
        return Command::SUCCESS;
    }
}


