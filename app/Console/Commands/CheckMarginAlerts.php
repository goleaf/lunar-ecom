<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvancedPricingService;
use App\Models\ProductVariant;

/**
 * Check margin alerts for all variants.
 * 
 * Run via cron:
 * 0 */6 * * * php artisan pricing:check-margin-alerts
 */
class CheckMarginAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pricing:check-margin-alerts 
                            {--threshold= : Minimum margin percentage threshold}
                            {--variant= : Check specific variant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and create margin alerts for variants';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $service = app(AdvancedPricingService::class);
        $threshold = $this->option('threshold') ? (float)$this->option('threshold') : null;
        $variantId = $this->option('variant');

        $this->info('Checking margin alerts...');

        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if (!$variant) {
                $this->error("Variant {$variantId} not found.");
                return Command::FAILURE;
            }
            $variants = collect([$variant]);
        } else {
            $variants = ProductVariant::whereNotNull('cost_price')
                ->where('cost_price', '>', 0)
                ->where('status', 'active')
                ->get();
        }

        $bar = $this->output->createProgressBar($variants->count());
        $bar->start();

        $alertsCreated = 0;

        foreach ($variants as $variant) {
            try {
                $alert = $service->checkMarginAlert($variant, null, $threshold);
                if ($alert) {
                    $alertsCreated++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Error checking variant {$variant->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Created {$alertsCreated} margin alerts.");

        return Command::SUCCESS;
    }
}


