<?php

namespace App\Notifications;

use App\Models\ProductQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuestionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ProductQuestion $question;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductQuestion $question)
    {
        $this->question = $question;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->question->product;
        $productName = $product->translateAttribute('name') ?? 'Product';
        $adminUrl = route('admin.products.questions.show', $this->question->id);

        return (new MailMessage)
            ->subject("New Question: {$productName}")
            ->greeting("Hello Admin,")
            ->line("A new question has been submitted for **{$productName}**.")
            ->line("**Question:**")
            ->line($this->question->question)
            ->line("**Asked by:** {$this->question->customer_name} ({$this->question->email})")
            ->action('View & Answer Question', $adminUrl)
            ->line('Please review and answer the question when convenient.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'question_id' => $this->question->id,
            'product_id' => $this->question->product_id,
            'product_name' => $this->question->product->translateAttribute('name'),
            'question' => $this->question->question,
            'customer_name' => $this->question->customer_name,
            'email' => $this->question->email,
        ];
    }
}


