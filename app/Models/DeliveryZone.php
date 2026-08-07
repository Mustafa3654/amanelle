<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class DeliveryZone extends Model
{
    use HasTranslations;

    protected $guarded = [];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'free_above' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Exactly one default, or the checkout would have to guess which
        // option to preselect.
        static::saved(function (self $zone) {
            if ($zone->is_default) {
                static::where('id', '!=', $zone->id)->update(['is_default' => false]);
            }
        });
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * What this zone costs for a given order subtotal.
     *
     * Charged in the base currency; the storefront converts it like any other
     * price, so a free-delivery threshold set in dollars still behaves
     * correctly for a customer browsing in pounds.
     */
    public function feeFor(float $subtotal): float
    {
        if ($this->free_above !== null && $subtotal >= (float) $this->free_above) {
            return 0.0;
        }

        return (float) $this->fee;
    }

    public function isFreeAt(float $subtotal): bool
    {
        return $this->feeFor($subtotal) === 0.0 && (float) $this->fee > 0;
    }
}
