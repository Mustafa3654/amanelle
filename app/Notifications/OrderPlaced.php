<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        // Filament's bell is always on because it needs no configuration.
        // Mail joins in once SMTP is set; until then MAIL_MAILER=log writes it
        // to storage/logs rather than pretending to have sent anything.
        $channels = ['database', 'mail'];

        if (config('services.telegram.token') && config('services.telegram.chat_id')) {
            $channels[] = \App\Notifications\Channels\TelegramChannel::class;
        }

        return $channels;
    }

    /**
     * Phone alert. Deliberately terse — this is read on a lock screen, so the
     * name, the phone number and what to pack are what matter.
     */
    public function toTelegram(object $notifiable): string
    {
        $lines = [
            "🛍 <b>New order {$this->order->number}</b>",
            '',
            "👤 {$this->order->customer_name}",
            "📞 {$this->order->customer_phone}",
            "📍 {$this->order->city} — {$this->order->shipping_address}",
            '',
        ];

        foreach ($this->order->items as $item) {
            $lines[] = "• {$item->product_name} ({$item->variant_label}) × {$item->quantity}";
        }

        $lines[] = '';
        $lines[] = '💵 <b>'.number_format((float) $this->order->total, 2).' USD</b>';

        if ($this->order->notes) {
            $lines[] = '';
            $lines[] = "📝 {$this->order->notes}";
        }

        return implode("\n", $lines);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("New order {$this->order->number}")
            ->greeting('New order')
            ->line("**{$this->order->customer_name}** — {$this->order->customer_phone}")
            ->line("{$this->order->city}, {$this->order->shipping_address}");

        foreach ($this->order->items as $item) {
            $mail->line("• {$item->product_name} ({$item->variant_label}) × {$item->quantity}");
        }

        return $mail
            ->line('**Total: '.number_format((float) $this->order->total, 2).' USD**')
            ->action('Open in admin', url("/admin/orders/{$this->order->id}/edit"));
    }

    /**
     * Shape Filament reads for the notification bell.
     */
    public function toDatabase(object $notifiable): array
    {
        $count = $this->order->items->sum('quantity');

        return [
            'title' => "New order {$this->order->number}",
            'body' => "{$this->order->customer_name} · {$count} item(s) · "
                .number_format((float) $this->order->total, 2).' USD',
            'icon' => 'heroicon-o-shopping-bag',
            'iconColor' => 'success',
            'format' => 'filament',
            'actions' => [],
            'order_id' => $this->order->id,
        ];
    }
}
