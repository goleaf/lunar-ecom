<?php

namespace App\Notifications;

use App\Models\ProductSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification for product schedule changes (admin).
 */
class ProductScheduleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ProductSchedule $schedule;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductSchedule $schedule)
    {
        $this->schedule = $schedule;
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
        $product = $this->schedule->product;
        $productName = $product->translateAttribute('name') ?? 'Product';
        $scheduledAt = $this->schedule->scheduled_at->format('M d, Y H:i A');
        $timezone = $this->schedule->timezone ?? 'UTC';

        $message = (new MailMessage)
            ->subject("Product Schedule Reminder: {$productName}")
            ->greeting("Hello!")
            ->line("This is a reminder that a product schedule is about to execute:")
            ->line("**Product:** {$productName}")
            ->line("**Schedule Type:** " . ucfirst($this->schedule->type))
            ->line("**Scheduled Time:** {$scheduledAt} ({$timezone})");

        if ($this->schedule->expires_at) {
            $expiresAt = $this->schedule->expires_at->format('M d, Y H:i A');
            $message->line("**Expires At:** {$expiresAt}");
        }

        if ($this->schedule->isFlashSale()) {
            $message->line("**Flash Sale:** Yes");
            if ($this->schedule->sale_percentage) {
                $message->line("**Discount:** {$this->schedule->sale_percentage}%");
            }
        }

        $message->action('View Schedule', route('admin.products.schedules.show', $this->schedule->id));

        return $message;
    }
}


