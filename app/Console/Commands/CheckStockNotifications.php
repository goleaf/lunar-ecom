<?php

namespace App\Console\Commands;

use App\Services\StockNotificationService;
use Illuminate\Console\Command;

/**
 * Command to check stock levels and send notifications.
 */
class CheckStockNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-notifications 
                            {--product= : Check specific product ID}
                            {--variant= : Check specific variant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check stock levels and send availability notifications';

    /**
     * Execute the console command.
     */
    public function handle(StockNotificationService $service): int
    {
        $this->info('Checking stock notifications...');

        if ($productId = $this->option('product')) {
            $product = \Lunar\Models\Product::find($productId);
            if (!$product) {
                $this->error("Product {$productId} not found.");
                return 1;
            }

            $sent = $service->checkAndNotify($product);
            $this->info("Sent {$sent} notification(s) for product {$productId}.");
            return 0;
        }

        if ($variantId = $this->option('variant')) {
            $variant = \Lunar\Models\ProductVariant::find($variantId);
            if (!$variant) {
                $this->error("Variant {$variantId} not found.");
                return 1;
            }

            $sent = $service->checkAndNotifyVariant($variant);
            $this->info("Sent {$sent} notification(s) for variant {$variantId}.");
            return 0;
        }

        // Check all products
        $stats = $service->checkAllProducts();
        $this->info("Checked {$stats['products_checked']} product(s).");
        $this->info("Sent {$stats['notifications_sent']} notification(s).");

        return 0;
    }
}

