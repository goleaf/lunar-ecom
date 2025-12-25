<?php

namespace App\Notifications;

use App\Models\Download;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification for digital product download availability.
 */
class DigitalProductDownloadAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    public Download $download;

    /**
     * Create a new notification instance.
     */
    public function __construct(Download $download)
    {
        $this->download = $download;
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
        $product = $this->download->digitalProduct->product;
        $productName = $product->translateAttribute('name');
        $downloadUrl = $this->download->getDownloadUrl();
        $order = $this->download->order;

        $message = (new MailMessage)
            ->subject("Your Digital Product Download: {$productName}")
            ->greeting("Hello!")
            ->line("Thank you for your purchase! Your digital product **{$productName}** is ready to download.")
            ->action('Download Now', $downloadUrl)
            ->line("**Order Number:** {$order->reference}")
            ->line("**Download Link:** [Click here to download]({$downloadUrl})");

        // Add license key if available
        if ($this->download->license_key) {
            $message->line("**License Key:** `{$this->download->license_key}`")
                ->line("Please save this license key for future reference.");
        }

        // Add download instructions
        $digitalProduct = $this->download->digitalProduct;
        if ($digitalProduct->download_limit) {
            $message->line("**Download Limit:** {$digitalProduct->download_limit} downloads");
        }

        if ($digitalProduct->download_expiry_days) {
            $message->line("**Download Expires:** " . $this->download->expires_at->format('F j, Y'));
        }

        $message->line("If you have any questions, please contact our support team.")
            ->salutation("Thank you for your purchase!");

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
            'download_id' => $this->download->id,
            'product_name' => $this->download->digitalProduct->product->translateAttribute('name'),
            'download_url' => $this->download->getDownloadUrl(),
        ];
    }
}

