<?php

namespace App\Console\Commands;

use App\Services\ProductWorkflowService;
use Illuminate\Console\Command;

/**
 * Command to process expired products.
 */
class ProcessProductExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:process-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired products and auto-archive if configured';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ProductWorkflowService $service): int
    {
        $this->info('Processing expired products...');
        
        $processed = $service->processExpiredProducts();
        
        $this->info("Processed {$processed} expired products.");
        
        return Command::SUCCESS;
    }
}

