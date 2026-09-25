<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmed extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Order confirmed — '.$this->order->order_number)
            ->greeting('Thanks for your order!')
            ->line('Your order '.$this->order->order_number.' has been paid and is ready.')
            ->line('Total: '.$this->order->totalFormatted());

        foreach ($this->order->items as $item) {
            $message->line('• '.$item->description);
        }

        return $message->action('View your purchases', route('account.purchases.index'))
            ->line('You can download your files and manage licenses from your account.');
    }
}
