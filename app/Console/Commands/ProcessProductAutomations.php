<?php

namespace App\Console\Commands;

use App\Services\ProductAutomationService;
use Illuminate\Console\Command;

/**
 * Command to process product automation rules.
 */
class ProcessProductAutomations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:process-automations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due product automation rules';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ProductAutomationService $service): int
    {
        $this->info('Processing product automation rules...');
        
        $processed = $service->processDueRules();
        
        $this->info("Processed {$processed} automation rules.");
        
        return Command::SUCCESS;
    }
}

