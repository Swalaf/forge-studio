<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewReplied extends Notification
{
    use Queueable;

    public function __construct(public Review $review)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('The author replied to your review of '.$this->review->product->title)
            ->greeting('You got a reply to your review')
            ->line('Your review: "'.\Illuminate\Support\Str::limit($this->review->body, 120).'"')
            ->line('Reply: '.$this->review->reply_body)
            ->action('View product', route('market.show', $this->review->product));
    }
}
