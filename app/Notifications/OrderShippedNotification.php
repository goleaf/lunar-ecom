<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Lunar\Models\Order;

/**
 * Notification sent when an order is shipped.
 * 
 * This is a specialized notification for shipped orders that may include
 * tracking information and delivery estimates.
 */
class OrderShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order,
        public string $status,
        public ?string $trackingNumber = null,
        public ?string $carrier = null
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
        $orderReference = $this->order->reference ?? "#{$this->order->id}";
        $shippingAddress = $this->order->shippingAddress;

        $message = (new MailMessage)
            ->subject("🎉 Your Order {$orderReference} Has Shipped!")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("Great news! Your order **{$orderReference}** has been shipped and is on its way to you.");

        if ($shippingAddress) {
            $message->line("**Shipping Address:**")
                ->line("{$shippingAddress->first_name} {$shippingAddress->last_name}")
                ->line($shippingAddress->line_one);
            if ($shippingAddress->line_two) {
                $message->line($shippingAddress->line_two);
            }
            $message->line("{$shippingAddress->city}, {$shippingAddress->state} {$shippingAddress->postcode}");
        }

        if ($this->trackingNumber) {
            $message->line("**Tracking Number:** {$this->trackingNumber}");
            if ($this->carrier) {
                $message->line("**Carrier:** {$this->carrier}");
            }
        }

        $message->line('You can track your order and view more details in your account.')
            ->action('Track Order', route('frontend.account.orders.show', $this->order->id))
            ->line('We hope you love your purchase!')
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
            'tracking_number' => $this->trackingNumber,
            'carrier' => $this->carrier,
        ];
    }
}


