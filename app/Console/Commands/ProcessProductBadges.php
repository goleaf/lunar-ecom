<?php

namespace App\Console\Commands;

use App\Services\ProductBadgeService;
use Illuminate\Console\Command;

/**
 * Command to process product badge auto-assignment.
 */
class ProcessProductBadges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:process-badges';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process auto-assignment of product badges and remove expired assignments';

    /**
     * Execute the console command.
     */
    public function handle(ProductBadgeService $service): int
    {
        $this->info('Processing product badges...');

        // Process auto-assignment
        $processed = $service->processAutoAssignment();
        $this->info("Processed {$processed} products for auto-assignment.");

        // Remove expired assignments
        $removed = $service->removeExpiredAssignments();
        $this->info("Removed {$removed} expired badge assignments.");

        return Command::SUCCESS;
    }
}

