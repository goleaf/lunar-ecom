<?php

namespace App\Console\Commands;

use App\Jobs\SendLowStockAlertNotification;
use App\Models\LowStockAlert;
use Illuminate\Console\Command;

/**
 * Command to check for low stock and send alerts.
 * 
 * Run this command hourly via scheduler:
 * $schedule->command('inventory:check-low-stock')->hourly();
 */
class CheckLowStockAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-low-stock {--send-emails : Send email notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for low stock items and create/send alerts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for low stock items...');

        $alerts = LowStockAlert::needsNotification()->get();

        $this->info("Found {$alerts->count()} low stock alert(s) needing notification.");

        if ($this->option('send-emails')) {
            foreach ($alerts as $alert) {
                SendLowStockAlertNotification::dispatch($alert);
                $this->line("Queued notification for alert ID: {$alert->id}");
            }
            $this->info("Queued {$alerts->count()} notification(s).");
        } else {
            $this->warn('Use --send-emails flag to send notifications.');
        }

        return Command::SUCCESS;
    }
}
