<?php

namespace App\Console\Commands;

use App\Models\ProductVariant;
use App\Models\InventoryLevel;
use App\Services\InventoryAutomationService;
use Illuminate\Console\Command;

/**
 * Process inventory automation rules.
 * 
 * Run this command periodically via scheduler:
 * $schedule->command('inventory:process-automation')->everyFiveMinutes();
 */
class ProcessInventoryAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:process-automation 
                            {--variant= : Process specific variant ID}
                            {--all : Process all variants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process inventory automation rules (alerts, auto-disable/enable, reorders)';

    /**
     * Execute the console command.
     */
    public function handle(InventoryAutomationService $service): int
    {
        $this->info('Processing inventory automation...');

        $variants = $this->getVariantsToProcess();

        if ($variants->isEmpty()) {
            $this->warn('No variants to process.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($variants->count());
        $bar->start();

        $results = [
            'alerts_created' => 0,
            'variants_disabled' => 0,
            'variants_enabled' => 0,
            'reorders_created' => 0,
            'triggers_created' => 0,
        ];

        foreach ($variants as $variant) {
            $warehouseId = null; // Process all warehouses
            $result = $service->processAutomation($variant, $warehouseId);

            // Aggregate results
            foreach ($result as $key => $value) {
                $results[$key] += $value;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Automation processing complete:');
        $this->line("  Alerts created: {$results['alerts_created']}");
        $this->line("  Variants disabled: {$results['variants_disabled']}");
        $this->line("  Variants enabled: {$results['variants_enabled']}");
        $this->line("  Reorders created: {$results['reorders_created']}");
        $this->line("  Triggers created: {$results['triggers_created']}");

        return Command::SUCCESS;
    }

    /**
     * Get variants to process.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getVariantsToProcess()
    {
        if ($variantId = $this->option('variant')) {
            return collect([ProductVariant::findOrFail($variantId)]);
        }

        if ($this->option('all')) {
            return ProductVariant::all();
        }

        // Default: Process variants with low stock or out of stock
        return ProductVariant::whereHas('inventoryLevels', function ($query) {
            $query->whereColumn('quantity', '<=', 'reorder_point')
                  ->orWhere('quantity', '<=', 0);
        })->get();
    }
}


