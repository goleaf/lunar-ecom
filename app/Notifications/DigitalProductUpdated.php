<?php

namespace App\Notifications;

use App\Models\DigitalProduct;
use App\Models\DigitalProductVersion;
use App\Models\Download;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification for digital product updates.
 */
class DigitalProductUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public DigitalProduct $digitalProduct;
    public DigitalProductVersion $version;
    public Download $download;

    /**
     * Create a new notification instance.
     */
    public function __construct(DigitalProduct $digitalProduct, DigitalProductVersion $version, Download $download)
    {
        $this->digitalProduct = $digitalProduct;
        $this->version = $version;
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
        $product = $this->digitalProduct->product;
        $productName = $product->translateAttribute('name');
        $downloadUrl = $this->download->getDownloadUrl() . '?version=' . $this->version->version;

        $message = (new MailMessage)
            ->subject("Update Available: {$productName} v{$this->version->version}")
            ->greeting("Hello!")
            ->line("A new version of **{$productName}** is now available!")
            ->line("**New Version:** {$this->version->version}")
            ->action('Download Update', $downloadUrl);

        if ($this->version->release_notes) {
            $message->line("**Release Notes:**")
                ->line($this->version->release_notes);
        }

        $message->line("You can download the update using your original download link.")
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
            'digital_product_id' => $this->digitalProduct->id,
            'version' => $this->version->version,
            'product_name' => $this->digitalProduct->product->translateAttribute('name'),
        ];
    }
}

