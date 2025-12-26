<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReservationService;

/**
 * Release expired reservations.
 * 
 * Run via cron:
 * * * * * * php artisan reservations:release-expired
 */
class ReleaseExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:release-expired 
                            {--limit=100 : Maximum number of reservations to release per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release expired stock reservations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $service = app(ReservationService::class);
        $limit = (int) $this->option('limit');

        $this->info('Releasing expired reservations...');

        $released = $service->releaseExpiredReservations($limit);

        $this->info("Released {$released} expired reservations.");

        // Also release expired locks
        $locksReleased = $service->releaseExpiredLocks();
        if ($locksReleased > 0) {
            $this->info("Released {$locksReleased} expired locks.");
        }

        return Command::SUCCESS;
    }
}


