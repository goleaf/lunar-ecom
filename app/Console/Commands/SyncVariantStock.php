<?php

namespace App\Console\Commands;

use App\Services\StockService;
use Illuminate\Console\Command;
use Lunar\Models\ProductVariant;

/**
 * Command to sync variant stock from inventory levels.
 * 
 * Run this command periodically via scheduler:
 * $schedule->command('inventory:sync-variant-stock')->hourly();
 */
class SyncVariantStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-variant-stock {--variant-id= : Sync specific variant only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync product variant stock from inventory levels across warehouses';

    /**
     * Execute the console command.
     */
    public function handle(StockService $stockService): int
    {
        $this->info('Syncing variant stock from inventory levels...');

        $variantId = $this->option('variant-id');

        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if (!$variant) {
                $this->error("Variant {$variantId} not found.");
                return Command::FAILURE;
            }
            $stockService->syncVariantStock($variant);
            $this->info("Synced variant {$variantId}.");
        } else {
            $variants = ProductVariant::whereHas('inventoryLevels')->get();
            $bar = $this->output->createProgressBar($variants->count());
            $bar->start();

            foreach ($variants as $variant) {
                $stockService->syncVariantStock($variant);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Synced {$variants->count()} variant(s).");
        }

        return Command::SUCCESS;
    }
}

