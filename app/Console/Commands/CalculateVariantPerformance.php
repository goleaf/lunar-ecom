<?php

namespace App\Console\Commands;

use App\Models\ProductVariant;
use App\Services\VariantPerformanceService;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * Command to calculate variant performance analytics.
 */
class CalculateVariantPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:calculate-variants 
                            {--date= : Date to calculate (Y-m-d, defaults to yesterday)}
                            {--period=daily : Period (daily, weekly, monthly)}
                            {--variant= : Specific variant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate variant performance analytics for a specific date';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(VariantPerformanceService $service): int
    {
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();
        
        $period = $this->option('period') ?? 'daily';
        $variantId = $this->option('variant');
        
        $this->info("Calculating variant performance for {$date->format('Y-m-d')} ({$period})...");
        
        $query = ProductVariant::query();
        if ($variantId) {
            $query->where('id', $variantId);
        }
        
        $variants = $query->get();
        $bar = $this->output->createProgressBar($variants->count());
        $bar->start();
        
        foreach ($variants as $variant) {
            try {
                $service->calculateForDate($variant, $date, $period);
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nError calculating performance for variant {$variant->id}: " . $e->getMessage());
            }
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Completed calculating performance for {$variants->count()} variants.");
        
        return Command::SUCCESS;
    }
}

