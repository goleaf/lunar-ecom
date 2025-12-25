<?php

namespace App\Services;

use App\Models\StockNotification;
use App\Notifications\ProductBackInStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

/**
 * Service for managing stock availability notifications.
 */
class StockNotificationService
{
    /**
     * Subscribe to stock notifications.
     *
     * @param  array  $data
     * @return StockNotification
     */
    public function subscribe(array $data): StockNotification
    {
        return DB::transaction(function () use ($data) {
            // Check if already subscribed
            $existing = StockNotification::where('email', $data['email'])
                ->where('product_id', $data['product_id'])
                ->where('product_variant_id', $data['product_variant_id'] ?? null)
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                return $existing;
            }

            // Create new subscription
            return StockNotification::create([
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'email' => $data['email'],
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'notify_on_backorder' => $data['notify_on_backorder'] ?? false,
                'min_quantity' => $data['min_quantity'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * Unsubscribe from stock notifications.
     *
     * @param  string  $token
     * @return bool
     */
    public function unsubscribe(string $token): bool
    {
        $notification = StockNotification::where('token', $token)->first();

        if (!$notification) {
            return false;
        }

        $notification->cancel();

        return true;
    }

    /**
     * Check and send notifications for a product.
     *
     * @param  Product  $product
     * @return int Number of notifications sent
     */
    public function checkAndNotify(Product $product): int
    {
        $notificationsSent = 0;

        // Get all pending notifications for this product
        $notifications = StockNotification::pending()
            ->forProduct($product->id)
            ->with(['product', 'productVariant'])
            ->get();

        foreach ($notifications as $notification) {
            if ($notification->shouldNotify()) {
                try {
                    $this->sendNotification($notification);
                    $notification->markAsNotified();
                    $notificationsSent++;
                } catch (\Exception $e) {
                    Log::error('Failed to send stock notification', [
                        'notification_id' => $notification->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $notificationsSent;
    }

    /**
     * Check and send notifications for a variant.
     *
     * @param  ProductVariant  $variant
     * @return int Number of notifications sent
     */
    public function checkAndNotifyVariant(ProductVariant $variant): int
    {
        $notificationsSent = 0;

        // Get all pending notifications for this variant
        $notifications = StockNotification::pending()
            ->forVariant($variant->id)
            ->with(['product', 'productVariant'])
            ->get();

        foreach ($notifications as $notification) {
            if ($notification->shouldNotify()) {
                try {
                    $this->sendNotification($notification);
                    $notification->markAsNotified();
                    $notificationsSent++;
                } catch (\Exception $e) {
                    Log::error('Failed to send stock notification', [
                        'notification_id' => $notification->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $notificationsSent;
    }

    /**
     * Send notification to subscriber.
     *
     * @param  StockNotification  $notification
     * @return void
     */
    protected function sendNotification(StockNotification $notification): void
    {
        // Dispatch job to send notification (job will handle metrics and actual sending)
        \App\Jobs\SendBackInStockNotification::dispatch($notification);
    }

    /**
     * Check all products and send notifications.
     *
     * @return array Statistics
     */
    public function checkAllProducts(): array
    {
        $stats = [
            'products_checked' => 0,
            'notifications_sent' => 0,
        ];

        // Get all products with pending notifications
        $products = Product::whereHas('stockNotifications', function ($query) {
            $query->where('status', 'pending');
        })->get();

        foreach ($products as $product) {
            $sent = $this->checkAndNotify($product);
            $stats['products_checked']++;
            $stats['notifications_sent'] += $sent;
        }

        return $stats;
    }

    /**
     * Get subscription status for email and product.
     *
     * @param  string  $email
     * @param  int  $productId
     * @param  int|null  $variantId
     * @return StockNotification|null
     */
    public function getSubscription(string $email, int $productId, ?int $variantId = null): ?StockNotification
    {
        return StockNotification::where('email', $email)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->where('status', 'pending')
            ->first();
    }

    /**
     * Get all subscriptions for an email.
     *
     * @param  string  $email
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSubscriptionsForEmail(string $email)
    {
        return StockNotification::where('email', $email)
            ->where('status', 'pending')
            ->with(['product', 'productVariant'])
            ->get();
    }

    /**
     * Process queue: check for products back in stock and send notifications.
     *
     * @param  ProductVariant|null  $variant  If provided, only check this variant
     * @return int  Number of notifications sent
     */
    public function processQueue(?ProductVariant $variant = null): int
    {
        if ($variant) {
            return $this->checkAndNotifyVariant($variant);
        }

        // Process all products
        $stats = $this->checkAllProducts();
        return $stats['notifications_sent'];
    }

    /**
     * Get notification metrics for a product variant.
     *
     * @param  ProductVariant  $variant
     * @return array
     */
    public function getMetrics(ProductVariant $variant): array
    {
        $metrics = \App\Models\StockNotificationMetric::where('product_variant_id', $variant->id)->get();

        $totalSent = $metrics->where('email_sent', true)->count();
        $totalDelivered = $metrics->where('email_delivered', true)->count();
        $totalOpened = $metrics->where('email_opened', true)->count();
        $totalClicked = $metrics->where('link_clicked', true)->count();
        $totalConverted = $metrics->where('converted', true)->count();

        return [
            'total_subscriptions' => StockNotification::forVariant($variant->id)->pending()->count(),
            'total_sent' => $totalSent,
            'delivery_rate' => $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 2) : 0,
            'open_rate' => $totalDelivered > 0 ? round(($totalOpened / $totalDelivered) * 100, 2) : 0,
            'click_through_rate' => $totalOpened > 0 ? round(($totalClicked / $totalOpened) * 100, 2) : 0,
            'conversion_rate' => $totalSent > 0 ? round(($totalConverted / $totalSent) * 100, 2) : 0,
            'total_conversions' => $totalConverted,
        ];
    }

    /**
     * Get active subscriptions for a variant.
     *
     * @param  ProductVariant  $variant
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSubscriptions(ProductVariant $variant)
    {
        return StockNotification::forVariant($variant->id)
            ->pending()
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Remove subscription after purchase.
     *
     * @param  int  $variantId
     * @param  string  $email
     * @return int  Number of subscriptions removed
     */
    public function removeAfterPurchase(int $variantId, string $email): int
    {
        return StockNotification::where('product_variant_id', $variantId)
            ->where('email', $email)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    /**
     * Clean up expired subscriptions (older than 90 days).
     *
     * @return int  Number of subscriptions removed
     */
    public function cleanupExpired(): int
    {
        // Remove subscriptions older than 90 days
        return StockNotification::where('created_at', '<', now()->subDays(90))
            ->where('status', 'pending')
            ->delete();
    }
}
