<?php

namespace App\Console\Commands;

use App\Services\ComparisonService;
use Illuminate\Console\Command;

/**
 * Command to clean up expired product comparisons.
 * 
 * Run this command daily via scheduler:
 * $schedule->command('comparison:cleanup-expired')->daily();
 */
class CleanupExpiredComparisons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comparison:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired product comparisons';

    /**
     * Execute the console command.
     */
    public function handle(ComparisonService $service): int
    {
        $this->info('Cleaning up expired comparisons...');

        $count = $service->cleanupExpired();

        $this->info("Deleted {$count} expired comparison(s).");

        return Command::SUCCESS;
    }
}


