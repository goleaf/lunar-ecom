<?php

namespace App\Console\Commands;

use App\Services\InventoryService;
use Illuminate\Console\Command;

/**
 * Command to release expired stock reservations.
 * 
 * Run this command every 5 minutes via scheduler:
 * $schedule->command('inventory:release-expired-reservations')->everyFiveMinutes();
 */
class ReleaseExpiredStockReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:release-expired-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release expired stock reservations';

    /**
     * Execute the console command.
     */
    public function handle(InventoryService $service): int
    {
        $this->info('Releasing expired stock reservations...');

        $count = $service->releaseExpiredReservations();

        $this->info("Released {$count} expired reservation(s).");

        return Command::SUCCESS;
    }
}
