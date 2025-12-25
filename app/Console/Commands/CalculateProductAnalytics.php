<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductAnalyticsService;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * Command to calculate product analytics.
 */
class CalculateProductAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:calculate-products 
                            {--date= : Date to calculate (Y-m-d, defaults to yesterday)}
                            {--period=daily : Period (daily, weekly, monthly)}
                            {--product= : Specific product ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate product analytics for a specific date';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ProductAnalyticsService $service): int
    {
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();
        
        $period = $this->option('period') ?? 'daily';
        $productId = $this->option('product');
        
        $this->info("Calculating analytics for {$date->format('Y-m-d')} ({$period})...");
        
        $query = Product::query();
        if ($productId) {
            $query->where('id', $productId);
        }
        
        $products = $query->get();
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();
        
        foreach ($products as $product) {
            try {
                $service->calculateForDate($product, $date, $period);
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nError calculating analytics for product {$product->id}: " . $e->getMessage());
            }
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Completed calculating analytics for {$products->count()} products.");
        
        return Command::SUCCESS;
    }
}

