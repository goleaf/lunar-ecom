<?php

namespace App\Console\Commands;

use App\Services\BadgeService;
use Illuminate\Console\Command;

/**
 * Command to process product badge auto-assignment.
 */
class ProcessProductBadges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:process-badges {--product-id= : Process specific product only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process auto-assignment of product badges based on rules';

    /**
     * Execute the console command.
     */
    public function handle(BadgeService $service): int
    {
        $this->info('Processing product badges...');

        $productId = $this->option('product-id');
        $product = $productId ? \App\Models\Product::find($productId) : null;

        // Evaluate automatic badges
        $assigned = $service->evaluateAutomaticBadges($product);
        $this->info("Assigned {$assigned} badges.");

        // Remove expired assignments
        $removed = \App\Models\ProductBadgeAssignment::where('expires_at', '<=', now())
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->info("Deactivated {$removed} expired badge assignments.");

        return Command::SUCCESS;
    }
}
