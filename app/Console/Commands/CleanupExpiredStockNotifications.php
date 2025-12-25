<?php

namespace App\Console\Commands;

use App\Services\StockNotificationService;
use Illuminate\Console\Command;

/**
 * Command to clean up expired stock notifications.
 * 
 * Run this command daily via scheduler:
 * $schedule->command('stock-notifications:cleanup-expired')->daily();
 */
class CleanupExpiredStockNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock-notifications:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired stock notification subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(StockNotificationService $service): int
    {
        $this->info('Cleaning up expired stock notifications...');

        $count = $service->cleanupExpired();

        $this->info("Deleted {$count} expired subscription(s).");

        return Command::SUCCESS;
    }
}

