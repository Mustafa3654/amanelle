<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PromoCode extends Model
{
    use HasTranslations;

    protected $guarded = [];

    public array $translatable = ['description'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (self $promo) => $promo->code = strtoupper(trim($promo->code)));
    }

    public function scopeUsable(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    /**
     * Why this code cannot be used on this subtotal, or null if it can.
     *
     * Returns the reason rather than a bare bool so the checkout can say
     * "spend $10 more" instead of "invalid code" — one of those recovers the
     * sale and the other loses it.
     */
    public function rejectionReason(float $subtotal): ?string
    {
        if (! $this->is_active) {
            return __('That code is no longer active.');
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return __('That code is not available yet.');
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return __('That code has expired.');
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return __('That code has been fully redeemed.');
        }

        if ($this->min_subtotal && $subtotal < (float) $this->min_subtotal) {
            return __('Spend :amount to use this code.', [
                'amount' => \App\Support\Money::format((float) $this->min_subtotal),
            ]);
        }

        return null;
    }

    /**
     * The amount this code takes off, in the base currency.
     *
     * Never exceeds the subtotal — a fixed discount larger than the order must
     * not produce a negative total the shop would owe the customer.
     */
    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percent'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->max_discount) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }

    public function label(): string
    {
        return $this->type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'%'
            : \App\Support\Money::format((float) $this->value);
    }
}
