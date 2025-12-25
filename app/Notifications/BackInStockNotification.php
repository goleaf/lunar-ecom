<?php

namespace App\Notifications;

use App\Models\StockNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Lunar\Models\ProductVariant;

/**
 * Notification for back-in-stock alerts.
 */
class BackInStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public StockNotification $stockNotification;
    public ProductVariant $variant;
    public ?\App\Models\StockNotificationMetric $metrics;

    /**
     * Create a new notification instance.
     */
    public function __construct(StockNotification $stockNotification, ProductVariant $variant, ?\App\Models\StockNotificationMetric $metrics = null)
    {
        $this->stockNotification = $stockNotification;
        $this->variant = $variant;
        $this->metrics = $metrics;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->variant->product;
        $productName = $product->translateAttribute('name');
        $productUrl = $product->urls->first()?->url ?? url('/products/' . $product->id);
        
        // Get price
        $price = $this->variant->base_price ?? $this->variant->price ?? 0;
        $formattedPrice = '$' . number_format($price / 100, 2);
        
        // Check if limited quantity
        $stock = $this->variant->stock ?? 0;
        $isLimited = $stock > 0 && $stock < 10;
        
        // Build cart URL with variant
        $cartUrl = url('/cart/add?variant_id=' . $this->variant->id);
        
        // Unsubscribe URL
        $unsubscribeUrl = url('/stock-notifications/unsubscribe/' . $this->stockNotification->token);

        // Get metrics ID for tracking
        $metricId = $this->metrics?->id;

        // Build tracking URLs
        $buyNowUrl = $metricId 
            ? url('/stock-notifications/track/click/' . $metricId . '/buy_now')
            : $cartUrl;
        $productPageUrl = $metricId
            ? url('/stock-notifications/track/click/' . $metricId . '/product_page')
            : $productUrl;
        $unsubscribeUrlWithTracking = $metricId
            ? url('/stock-notifications/track/click/' . $metricId . '/unsubscribe')
            : $unsubscribeUrl;

        $message = (new MailMessage)
            ->subject($isLimited 
                ? "{$productName} is Back in Stock - Limited Quantity Available!" 
                : "{$productName} is Back in Stock!")
            ->greeting("Hello!")
            ->line($isLimited
                ? "Great news! **{$productName}** is back in stock, but quantities are limited!"
                : "Great news! **{$productName}** is back in stock and ready to order!")
            ->action('Buy Now', $buyNowUrl)
            ->line("**Price:** {$formattedPrice}")
            ->line("Don't miss out - [View Product]({$productPageUrl})")
            ->line('Thank you for your interest!');

        // Add product image if available (embedded in email)
        if ($product->thumbnail) {
            $message->line('![Product Image](' . $product->thumbnail->getUrl() . ')');
        }

        // Use custom view for better email design with tracking
        if ($metricId) {
            $trackingPixelUrl = url('/stock-notifications/track/open/' . $metricId);
            $mailMessage = new MailMessage();
            $mailMessage->subject($isLimited 
                ? "{$productName} is Back in Stock - Limited Quantity Available!" 
                : "{$productName} is Back in Stock!");
            $mailMessage->view('emails.back-in-stock', [
                'productName' => $productName,
                'productUrl' => $productPageUrl,
                'buyNowUrl' => $buyNowUrl,
                'formattedPrice' => $formattedPrice,
                'isLimited' => $isLimited,
                'unsubscribeUrl' => $unsubscribeUrlWithTracking,
                'productImage' => $product->thumbnail?->getUrl(),
                'trackingPixelUrl' => $trackingPixelUrl,
            ]);
            return $mailMessage;
        }

        // Fallback to simple message if no metrics
        $message->line('---')
            ->line("[Unsubscribe from back-in-stock notifications]({$unsubscribeUrlWithTracking})");

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'product_variant_id' => $this->variant->id,
            'product_name' => $this->variant->product->translateAttribute('name'),
            'notification_id' => $this->stockNotification->id,
        ];
    }
}

