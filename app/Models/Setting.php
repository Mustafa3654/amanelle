<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings'));
        static::deleted(fn () => Cache::forget('settings'));
    }

    /**
     * Settings are read on nearly every request and written rarely, so the
     * whole table is cached as one array and busted on save.
     */
    public static function all_(): array
    {
        return Cache::rememberForever('settings', fn () => static::query()
            ->pluck('value', 'key')
            ->map(fn ($v) => $v['v'] ?? null)
            ->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all_()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => ['v' => $value]]);
    }

    /**
     * Store a credential encrypted at rest.
     *
     * A Telegram bot token is enough to impersonate the shop's alerts, so it
     * should not sit in plaintext in a database backup.
     */
    public static function putEncrypted(string $key, ?string $value): void
    {
        static::put($key, $value === null ? null : Crypt::encryptString($value));
    }

    public static function getEncrypted(string $key): ?string
    {
        $stored = static::get($key);

        if (! is_string($stored) || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            // A rotated APP_KEY makes old values unreadable. Returning null
            // degrades to "not configured" rather than throwing on every
            // request that touches settings.
            return null;
        }
    }
}
