<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pushes order alerts to a Telegram chat.
 *
 * Chosen because it is the only instant-to-phone channel that needs no
 * business verification, no per-message cost and no approved template: create
 * a bot with @BotFather, put the token and chat id in .env, done. WhatsApp
 * reaches the same phone but requires a verified Business account and
 * pre-approved message templates before it will deliver anything.
 */
class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        $token = \App\Support\Telegram::token();
        $chatId = \App\Support\Telegram::chatId();

        // Unconfigured is the normal state until the shop owner sets it up, so
        // it is a silent no-op rather than a failed job on every order.
        if (! $token || ! $chatId) {
            return;
        }

        $response = Http::timeout(10)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $notification->toTelegram($notifiable),
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

        if ($response->failed()) {
            // Never let a notification failure roll back or obscure the order
            // itself — the sale is what matters, the alert is secondary.
            Log::warning('Telegram order alert failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
