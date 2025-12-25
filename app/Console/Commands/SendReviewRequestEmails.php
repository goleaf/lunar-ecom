<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Notifications\ReviewRequestNotification;
use Illuminate\Console\Command;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;

/**
 * Command to send review request emails to customers after delivery.
 * 
 * Run this command daily via scheduler:
 * $schedule->command('reviews:send-requests')->daily();
 */
class SendReviewRequestEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:send-requests 
                            {--days=7 : Number of days after delivery to send request}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send review request emails to customers after order delivery';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Finding orders delivered {$days} days ago...");

        // Find orders delivered X days ago
        $deliveryDate = now()->subDays($days)->startOfDay();
        
        // Get orders that were delivered around this date
        // Note: Adjust this query based on your order delivery tracking
        $orders = Order::where('placed_at', '<=', $deliveryDate)
            ->where('placed_at', '>=', $deliveryDate->copy()->subDay())
            ->whereNotNull('user_id')
            ->with(['lines.purchasable'])
            ->get();

        $this->info("Found {$orders->count()} orders to process.");

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($orders as $order) {
            // Get unique products from order
            $productIds = $order->lines()
                ->whereHas('purchasable', function ($q) {
                    $q->whereNotNull('product_id');
                })
                ->get()
                ->map(function ($line) {
                    return $line->purchasable->product_id ?? null;
                })
                ->filter()
                ->unique();

            foreach ($productIds as $productId) {
                $product = Product::find($productId);
                if (!$product) {
                    continue;
                }

                // Check if customer already reviewed this product
                $hasReview = \App\Models\Review::where('product_id', $productId)
                    ->where('customer_id', $order->user_id)
                    ->exists();

                if ($hasReview) {
                    $skippedCount++;
                    continue;
                }

                // Check if we already sent a review request for this order/product
                $hasNotification = \Illuminate\Notifications\DatabaseNotification::where('notifiable_id', $order->user_id)
                    ->where('notifiable_type', \Lunar\Models\Customer::class)
                    ->where('type', ReviewRequestNotification::class)
                    ->whereJsonContains('data->product_id', $productId)
                    ->whereJsonContains('data->order_reference', $order->reference)
                    ->exists();

                if ($hasNotification) {
                    $skippedCount++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("Would send review request for product {$product->id} to customer {$order->user_id}");
                    $sentCount++;
                } else {
                    $customer = \Lunar\Models\Customer::find($order->user_id);
                    if ($customer) {
                        $customer->notify(new ReviewRequestNotification($product, $order->reference));
                        $sentCount++;
                    }
                }
            }
        }

        if ($dryRun) {
            $this->info("Dry run complete. Would send {$sentCount} review requests, skipped {$skippedCount}.");
        } else {
            $this->info("Sent {$sentCount} review requests, skipped {$skippedCount}.");
        }

        return Command::SUCCESS;
    }
}
