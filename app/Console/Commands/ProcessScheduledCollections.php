<?php

namespace App\Console\Commands;

use App\Services\CollectionSchedulingService;
use Illuminate\Console\Command;

class ProcessScheduledCollections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collections:process-scheduled 
                            {--dry-run : Run without making changes}
                            {--publish-only : Only process scheduled publishes}
                            {--unpublish-only : Only process scheduled unpublishes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled collections and auto-publish/unpublish products';

    /**
     * Execute the console command.
     */
    public function handle(CollectionSchedulingService $service): int
    {
        $this->info('Processing scheduled collections...');

        $dryRun = $this->option('dry-run');
        $publishOnly = $this->option('publish-only');
        $unpublishOnly = $this->option('unpublish-only');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $results = [];

        if (!$unpublishOnly) {
            $this->info('Processing scheduled publishes...');
            $published = $service->processScheduledPublishes();
            $results['published'] = $published;
            
            $this->displayResults($published, 'published');
        }

        if (!$publishOnly) {
            $this->info('Processing scheduled unpublishes...');
            $unpublished = $service->processScheduledUnpublishes();
            $results['unpublished'] = $unpublished;
            
            $this->displayResults($unpublished, 'unpublished');
        }

        $totalProcessed = ($results['published']->count() ?? 0) + ($results['unpublished']->count() ?? 0);
        $totalSuccess = ($results['published']->where('success', true)->count() ?? 0) 
                      + ($results['unpublished']->where('success', true)->count() ?? 0);

        $this->newLine();
        $this->info("Processed {$totalProcessed} collections ({$totalSuccess} successful)");

        return Command::SUCCESS;
    }

    /**
     * Display results for a specific action.
     *
     * @param  \Illuminate\Support\Collection  $results
     * @param  string  $action
     * @return void
     */
    protected function displayResults($results, string $action): void
    {
        if ($results->isEmpty()) {
            $this->line("No collections scheduled for {$action}.");
            return;
        }

        $this->table(
            ['Collection ID', 'Collection Name', 'Status', 'Error'],
            $results->map(function ($result) {
                return [
                    $result['collection']->id,
                    $result['collection']->translateAttribute('name') ?? "Collection #{$result['collection']->id}",
                    $result['success'] ? '<info>Success</info>' : '<error>Failed</error>',
                    $result['error'] ?? '-',
                ];
            })->toArray()
        );
    }
}


