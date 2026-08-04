<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Currency extends Model
{
    use HasTranslations;

    protected $guarded = [];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
            'rate_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // The base currency is the unit every rate is quoted against, so a
        // rate other than 1 on it would silently rescale the whole catalogue.
        static::saving(function (self $currency) {
            if ($currency->is_base) {
                $currency->rate = 1;
            }

            if ($currency->isDirty('rate')) {
                $currency->rate_updated_at = now();
            }
        });

        // Rates are cached for the storefront; an edit in the admin has to
        // reach the shop immediately or prices go stale mid-session.
        static::saved(fn () => \App\Support\Money::flush());
        static::deleted(fn () => \App\Support\Money::flush());
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Convert an amount held in the base currency into this one.
     */
    public function convert(float $baseAmount): float
    {
        return round($baseAmount * (float) $this->rate, $this->decimals);
    }

    /**
     * A price is a number and a symbol that must stay in that order.
     *
     * Left as bare text in an RTL page, the bidi algorithm reorders "47.00 $"
     * into "$ 47.00" — wrong, and it looked like a formatting bug on the
     * Arabic storefront. <bdi> isolates the run so it renders the same in both
     * directions.
     */
    public function format(float $baseAmount): string
    {
        $value = number_format($this->convert($baseAmount), $this->decimals);

        return '<bdi>'.e($value).' '.e($this->symbol).'</bdi>';
    }

    /** The same amount as plain text, for emails, alerts and page titles. */
    public function formatPlain(float $baseAmount): string
    {
        return number_format($this->convert($baseAmount), $this->decimals).' '.$this->symbol;
    }
}
