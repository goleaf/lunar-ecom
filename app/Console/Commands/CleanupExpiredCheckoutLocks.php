<?php

namespace App\Console\Commands;

use App\Services\CheckoutService;
use Illuminate\Console\Command;

/**
 * Command to cleanup expired checkout locks and release reservations.
 */
class CleanupExpiredCheckoutLocks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'checkout:cleanup-expired-locks';

    /**
     * The console command description.
     */
    protected $description = 'Cleanup expired checkout locks and release stock reservations';

    public function __construct(
        protected CheckoutService $checkoutService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cleaning up expired checkout locks...');

        $count = $this->checkoutService->cleanupExpiredLocks();

        $this->info("Cleaned up {$count} expired checkout locks.");

        return Command::SUCCESS;
    }
}

