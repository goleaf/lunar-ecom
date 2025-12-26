<?php

namespace App\Notifications;

use App\Models\ComingSoonNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification for when a coming soon product becomes available.
 */
class ProductComingSoonAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    public ComingSoonNotification $notification;

    /**
     * Create a new notification instance.
     */
    public function __construct(ComingSoonNotification $notification)
    {
        $this->notification = $notification;
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
        $product = $this->notification->product;
        $productName = $product->translateAttribute('name') ?? 'Product';
        $productUrl = route('frontend.products.show', $product->id);

        return (new MailMessage)
            ->subject("Great News! {$productName} is Now Available!")
            ->greeting("Hello!")
            ->line("Good news! The product you were waiting for is now available:")
            ->line("**{$productName}**")
            ->action('View Product', $productUrl)
            ->line('Thank you for your interest!')
            ->line('If you no longer wish to receive these notifications, you can unsubscribe using the link below.');
    }
}



