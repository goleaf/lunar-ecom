<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductVariant;
use App\Services\VariantAnalyticsService;
use Carbon\Carbon;

/**
 * Calculate variant analytics.
 * 
 * Run this command via cron:
 * 0 2 * * * php artisan variants:calculate-analytics
 */
class CalculateVariantAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'variants:calculate-analytics 
                            {--date= : Date to calculate for (Y-m-d format)}
                            {--period=daily : Period (daily, weekly, monthly)}
                            {--all : Calculate for all variants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate analytics for variants';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $service = app(VariantAnalyticsService::class);
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $period = $this->option('period') ?? 'daily';
        $all = $this->option('all') ?? false;

        if ($all) {
            $variants = ProductVariant::where('status', 'active')->get();
            $this->info("Calculating analytics for {$variants->count()} variants...");
            
            $bar = $this->output->createProgressBar($variants->count());
            $bar->start();

            foreach ($variants as $variant) {
                $service->storeAnalytics($variant, $date, $period);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('Analytics calculated successfully.');
        } else {
            $this->info("Calculating analytics for date: {$date->toDateString()}, period: {$period}");
            // Would need variant ID or other selection criteria
        }

        // Update popularity rankings
        $this->info('Updating popularity rankings...');
        $updated = $service->updatePopularityRankings();
        $this->info("Updated popularity rankings for {$updated} variants.");

        return Command::SUCCESS;
    }
}


