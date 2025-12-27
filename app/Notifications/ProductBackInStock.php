<?php

namespace App\Notifications;

use App\Models\StockNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification for when a product is back in stock.
 */
class ProductBackInStock extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  StockNotification  $stockNotification
     */
    public function __construct(
        protected StockNotification $stockNotification
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $product = $this->stockNotification->product;
        $variant = $this->stockNotification->getVariant();
        $productUrl = route('frontend.products.show', $product);
        
        // Get pricing
        $currency = \Lunar\Models\Currency::getDefault();
        $price = null;
        if ($variant) {
            $pricing = \Lunar\Facades\Pricing::for($variant)->get();
            if ($pricing->matched?->price) {
                $price = $pricing->matched->price->value;
            }
        }

        $message = (new MailMessage)
            ->subject(__('notifications.stock.subject', ['product' => $product->translateAttribute('name')]))
            ->greeting(__('notifications.stock.greeting', ['name' => $this->stockNotification->name ?? 'Customer']))
            ->line(__('notifications.stock.line1', ['product' => $product->translateAttribute('name')]))
            ->action(__('notifications.stock.action'), $productUrl);

        if ($price) {
            $message->line(__('notifications.stock.price', [
                'price' => $currency->formatter($price),
            ]));
        }

        if ($variant && $variant->stock > 0) {
            $message->line(__('notifications.stock.stock_available', [
                'quantity' => $variant->stock,
            ]));
        }

        $message->line(__('notifications.stock.line2'))
            ->salutation(__('notifications.stock.salutation', [
                'store_name' => config('app.name', 'Store'),
            ]));

        // Add unsubscribe link
        $unsubscribeUrl = route('frontend.stock-notifications.unsubscribe', [
            'token' => $this->stockNotification->token,
        ]);
        $message->line(__('notifications.stock.unsubscribe', [
            'url' => $unsubscribeUrl,
        ]));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'product_id' => $this->stockNotification->product_id,
            'product_name' => $this->stockNotification->product->translateAttribute('name'),
            'product_url' => route('frontend.products.show', $this->stockNotification->product),
        ];
    }
}


