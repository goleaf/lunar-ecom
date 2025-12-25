<?php

namespace App\Console\Commands;

use App\Services\RecommendationService;
use Illuminate\Console\Command;

/**
 * Command to update product purchase associations from order history.
 * 
 * Run this command daily via scheduler:
 * $schedule->command('recommendations:update-associations')->daily();
 */
class UpdatePurchaseAssociations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recommendations:update-associations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update product purchase associations and calculate association rule metrics';

    /**
     * Execute the console command.
     */
    public function handle(RecommendationService $service): int
    {
        $this->info('Updating purchase associations...');

        $service->updatePurchaseAssociations();

        $this->info('Purchase associations updated successfully!');

        return Command::SUCCESS;
    }
}
