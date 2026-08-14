<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = [];

    public array $translatable = ['shade_name'];

    protected static function booted(): void
    {
        static::creating(function (self $variant): void {
            $product = $variant->relationLoaded('product')
                ? $variant->product
                : Product::find($variant->product_id);
            $prefix = str($product?->slug ?: 'PRODUCT')->upper()->replace('-', '')->limit(12, '');

            if (blank($variant->sku)) {
                do {
                    $sku = 'SKU-'.$prefix.'-'.str()->upper(Str::random(6));
                } while (self::where('sku', $sku)->exists());
                $variant->sku = $sku;
            }

            if (blank($variant->item_code)) {
                do {
                    $itemCode = 'ITEM-'.str()->upper(Str::random(8));
                } while (self::where('item_code', $itemCode)->exists());
                $variant->item_code = $itemCode;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
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
        /*
         * Every axis the variant actually uses, joined.
         *
         * The shade used to short-circuit and return alone, which was fine
         * while a product could only vary one way. A foundation or a tinted
         * blush varies by both, and "Desert Rose" on its own gives the
         * customer no way to tell the 30ml from the 50ml.
         */
        return collect([
            $this->shade_name ?: null,
            $this->volume_ml ? "{$this->volume_ml}ml" : null,
            $this->concentration ? strtoupper($this->concentration) : null,
        ])->filter()->implode(' · ');
    }
}
