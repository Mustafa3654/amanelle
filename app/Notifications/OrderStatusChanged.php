<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the customer where their order is.
 *
 * Only fires for statuses a customer would care about. "Processing" is
 * internal noise — they placed the order, of course you are processing it.
 */
class OrderStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public static function shouldNotifyFor(string $status): bool
    {
        return in_array($status, ['shipped', 'delivered', 'cancelled'], true);
    }

    public function via(object $notifiable): array
    {
        // Email is optional at checkout — this market orders by phone — so
        // there is often nowhere to send. The order page is the fallback,
        // which is why its link is in every message.
        return $this->order->customer_email ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('order.confirmation', $this->order->number);

        return match ($this->order->status) {
            'shipped' => (new MailMessage)
                ->subject("Your order {$this->order->number} is on its way")
                ->greeting("Hello {$this->order->customer_name},")
                ->line('Your order has left us and is on its way to you.')
                ->line("We will call you on {$this->order->customer_phone} to arrange delivery.")
                ->action('Track your order', $url)
                ->line('Payment is cash on delivery.'),

            'delivered' => (new MailMessage)
                ->subject("Your order {$this->order->number} has arrived")
                ->greeting("Hello {$this->order->customer_name},")
                ->line('Your order has been delivered. We hope you love it.')
                ->line('If anything is not right, just reply to this email.')
                ->action('View your order', $url),

            default => (new MailMessage)
                ->subject("Your order {$this->order->number} has been cancelled")
                ->greeting("Hello {$this->order->customer_name},")
                ->line('Your order has been cancelled and nothing has been charged.')
                ->line('If this was not what you expected, reply and we will sort it out.')
                ->action('View your order', $url),
        };
    }
}
