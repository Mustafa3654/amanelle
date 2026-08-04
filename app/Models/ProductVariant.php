<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ProductVariant extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = [];

    public array $translatable = ['shade_name'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Sellable units in one market. Reserved stock belongs to carts that have
     * not checked out yet, so it is subtracted rather than counted.
     */
    public function availableIn(string $market): int
    {
        $inventory = $this->inventories->firstWhere('market', $market);

        return $inventory ? max(0, $inventory->quantity - $inventory->reserved) : 0;
    }

    /**
     * The label a customer picks from — the axis that actually varies for this
     * product type. A perfume reads "100ml · EDP", a lipstick reads its shade.
     */
    public function label(): string
    {
        if ($this->shade_name) {
            return $this->shade_name;
        }

        return collect([
            $this->volume_ml ? "{$this->volume_ml}ml" : null,
            $this->concentration ? strtoupper($this->concentration) : null,
        ])->filter()->implode(' · ');
    }
}
