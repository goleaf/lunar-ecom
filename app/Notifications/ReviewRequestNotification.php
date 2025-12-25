<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to customers after delivery asking for review.
 */
class ReviewRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Product $product,
        public string $orderReference
    ) {}

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
        $productName = $this->product->translateAttribute('name');
        $reviewUrl = route('storefront.products.show', $this->product->slug ?? $this->product->id) . '#reviews';

        return (new MailMessage)
            ->subject("How was your experience with {$productName}?")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("We hope you're enjoying your purchase of **{$productName}**!")
            ->line('Your feedback helps other customers make informed decisions. We would love to hear about your experience.')
            ->action('Write a Review', $reviewUrl)
            ->line('Thank you for being a valued customer!')
            ->salutation('Best regards,');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->translateAttribute('name'),
            'order_reference' => $this->orderReference,
        ];
    }
}
