<?php

namespace App\Console\Commands;

use App\Services\CollectionManagementService;
use App\Services\SmartCollectionService;
use Illuminate\Console\Command;

/**
 * Command to process collection auto-assignments.
 */
class ProcessCollectionAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collections:process-assignments {--collection= : Process specific collection by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automatic product assignments for collections';

    /**
     * Execute the console command.
     */
    public function handle(CollectionManagementService $service, SmartCollectionService $smartService): int
    {
        $collectionId = $this->option('collection');

        if ($collectionId) {
            $collection = \App\Models\Collection::find($collectionId);
            
            if (!$collection) {
                $this->error("Collection with ID {$collectionId} not found.");
                return Command::FAILURE;
            }

            $this->info("Processing collection: {$collection->name}...");
            $assigned = $service->processAutoAssignment($collection);
            $this->info("Assigned {$assigned} products to collection.");
        } else {
            $this->info('Processing all smart collections...');
            $smartService->processAllSmartCollections();
            
            $this->info('Processing all auto-assign collections...');
            $processed = $service->processAllAutoAssignments();
            $this->info("Processed {$processed} collections.");
        }

        // Remove expired assignments
        $removed = $service->removeExpiredAssignments();
        $this->info("Removed {$removed} expired product assignments.");

        return Command::SUCCESS;
    }
}

