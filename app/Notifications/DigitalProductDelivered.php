<?php

namespace App\Notifications;

use App\Models\DownloadLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification for digital product delivery.
 */
class DigitalProductDelivered extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  DownloadLink  $downloadLink
     */
    public function __construct(
        protected DownloadLink $downloadLink
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
     * Route notifications for the mail channel.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    public function routeNotificationForMail($notifiable)
    {
        return $this->downloadLink->email;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $product = $this->downloadLink->productVariant->product;
        $downloadUrl = route('frontend.downloads.download', [
            'token' => $this->downloadLink->token,
        ]);

        $message = (new MailMessage)
            ->subject(__('notifications.digital.subject', [
                'product' => $product->translateAttribute('name'),
            ]))
            ->greeting(__('notifications.digital.greeting'))
            ->line(__('notifications.digital.line1', [
                'product' => $product->translateAttribute('name'),
                'order' => $this->downloadLink->order->reference,
            ]))
            ->action(__('notifications.digital.action'), $downloadUrl);

        // Add download information
        if ($this->downloadLink->download_limit) {
            $message->line(__('notifications.digital.download_limit', [
                'limit' => $this->downloadLink->download_limit,
            ]));
        }

        if ($this->downloadLink->expires_at) {
            $message->line(__('notifications.digital.expires_at', [
                'date' => $this->downloadLink->expires_at->format('F j, Y'),
            ]));
        }

        $message->line(__('notifications.digital.line2'))
            ->salutation(__('notifications.digital.salutation', [
                'store_name' => config('app.name', 'Store'),
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
            'download_link_id' => $this->downloadLink->id,
            'product_name' => $this->downloadLink->productVariant->product->translateAttribute('name'),
            'order_reference' => $this->downloadLink->order->reference,
            'download_url' => route('frontend.downloads.download', ['token' => $this->downloadLink->token]),
        ];
    }
}


