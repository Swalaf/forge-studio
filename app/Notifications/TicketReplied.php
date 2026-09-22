<?php

namespace App\Notifications;

use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketReplied extends Notification
{
    use Queueable;

    public function __construct(public TicketMessage $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->message->ticket;

        return (new MailMessage)
            ->subject('New reply on: '.$ticket->subject)
            ->greeting('New reply on your support ticket')
            ->line($this->message->author->name.' replied:')
            ->line($this->message->body)
            ->action('View ticket', url('/'));
    }
}
