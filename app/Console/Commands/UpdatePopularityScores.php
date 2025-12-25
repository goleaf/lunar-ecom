<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SearchRankingService;

/**
 * Command to update popularity scores for all products.
 */
class UpdatePopularityScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-popularity-scores 
                            {--chunk=100 : Number of products to process at a time}
                            {--product= : Update specific product by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update popularity scores for products based on views, orders, reviews, and ratings';

    /**
     * Execute the console command.
     */
    public function handle(SearchRankingService $rankingService): int
    {
        $this->info('Updating popularity scores...');

        if ($productId = $this->option('product')) {
            $product = \App\Models\Product::find($productId);
            
            if (!$product) {
                $this->error("Product with ID {$productId} not found.");
                return Command::FAILURE;
            }

            $rankingService->updatePopularityScore($product);
            $this->info("Updated popularity score for product #{$productId}: {$product->popularity_score}");
            
            return Command::SUCCESS;
        }

        $chunkSize = (int) $this->option('chunk');
        $this->info("Processing in chunks of {$chunkSize}...");

        $bar = $this->output->createProgressBar();
        $bar->start();

        $count = $rankingService->updateAllPopularityScores($chunkSize);

        $bar->finish();
        $this->newLine();
        $this->info("Updated popularity scores for {$count} products.");

        return Command::SUCCESS;
    }
}

