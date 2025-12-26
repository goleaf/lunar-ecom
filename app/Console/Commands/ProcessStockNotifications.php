<?php

namespace App\Console\Commands;

use App\Services\StockNotificationService;
use Illuminate\Console\Command;

/**
 * Command to process stock notification queue.
 * 
 * Run this command periodically via scheduler:
 * $schedule->command('stock-notifications:process')->everyFiveMinutes();
 */
class ProcessStockNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock-notifications:process {--variant-id= : Process notifications for a specific variant only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process back-in-stock notification queue';

    /**
     * Execute the console command.
     */
    public function handle(StockNotificationService $service): int
    {
        $this->info('Processing stock notification queue...');

        $variantId = $this->option('variant-id');
        $variant = $variantId ? \Lunar\Models\ProductVariant::find($variantId) : null;

        $sentCount = $service->processQueue($variant);

        $this->info("Sent {$sentCount} back-in-stock notification(s).");

        return Command::SUCCESS;
    }
}


