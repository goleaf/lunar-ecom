<?php

namespace App\Notifications;

use App\Models\ProductQuestion;
use App\Models\ProductAnswer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuestionAnsweredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ProductQuestion $question;
    public ProductAnswer $answer;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductQuestion $question, ProductAnswer $answer)
    {
        $this->question = $question;
        $this->answer = $answer;
    }

    /**
     * Get the notification's delivery channels.
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
        $product = $this->question->product;
        $productName = $product->translateAttribute('name') ?? 'Product';
        $productUrl = route('storefront.products.show', $product->id);

        $message = (new MailMessage)
            ->subject("Your question about {$productName} has been answered!")
            ->greeting("Hello {$this->question->customer_name},")
            ->line("Great news! Your question about **{$productName}** has been answered.")
            ->line("**Your Question:**")
            ->line($this->question->question)
            ->line("**Answer:**")
            ->line($this->answer->answer);

        if ($this->answer->is_official) {
            $message->line("This is an official answer from the store.");
        }

        $message->action('View Question & Answer', $productUrl . '#qa')
            ->line('Thank you for your question!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'question_id' => $this->question->id,
            'answer_id' => $this->answer->id,
            'product_id' => $this->question->product_id,
            'product_name' => $this->question->product->translateAttribute('name'),
        ];
    }
}

