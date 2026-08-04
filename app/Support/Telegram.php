<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Resolves the Telegram credentials.
 *
 * The admin panel is the primary source so the shop owner can change it
 * without touching a server. The .env values stay as a fallback for anyone
 * who configured it that way before, and for deployments that prefer to keep
 * credentials out of the database entirely.
 */
class Telegram
{
    public static function token(): ?string
    {
        return Setting::getEncrypted('telegram_token')
            ?: config('services.telegram.token');
    }

    public static function chatId(): ?string
    {
        $stored = Setting::get('telegram_chat_id');

        return (is_string($stored) && $stored !== '')
            ? $stored
            : config('services.telegram.chat_id');
    }

    public static function isConfigured(): bool
    {
        return filled(static::token()) && filled(static::chatId());
    }
}
