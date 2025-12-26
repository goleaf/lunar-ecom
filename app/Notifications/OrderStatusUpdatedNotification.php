<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Lunar\Models\Order;

/**
 * Notification sent when an order status is updated.
 */
class OrderStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order,
        public string $status
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
        $statusConfig = config("lunar.orders.statuses.{$this->status}", []);
        $statusLabel = $statusConfig['label'] ?? ucfirst($this->status);
        $orderReference = $this->order->reference ?? "#{$this->order->id}";

        $message = (new MailMessage)
            ->subject("Order {$orderReference} - Status Updated")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("Your order **{$orderReference}** status has been updated to **{$statusLabel}**.");

        // Add status-specific messaging
        switch ($this->status) {
            case 'processing':
                $message->line('Your order is now being processed and will be prepared for shipment soon.');
                break;
            case 'completed':
                $message->line('Your order has been completed. Thank you for your purchase!');
                break;
            case 'cancelled':
                $message->line('Your order has been cancelled. If you have any questions, please contact our support team.');
                break;
        }

        $message->action('View Order', route('frontend.account.orders.show', $this->order->id))
            ->line('Thank you for shopping with us!')
            ->salutation('Best regards,');

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
            'order_id' => $this->order->id,
            'order_reference' => $this->order->reference,
            'status' => $this->status,
            'status_label' => config("lunar.orders.statuses.{$this->status}.label", $this->status),
        ];
    }
}


