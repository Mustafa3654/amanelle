<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventories';

    protected $guarded = [];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function available(): int
    {
        return max(0, $this->quantity - $this->reserved);
    }

    public function isLow(): bool
    {
        return $this->available() <= $this->low_stock_threshold;
    }

    /**
     * Powers the admin's low-stock alert. Compares against the per-row
     * threshold, so a fast-moving bestseller can warn earlier than a slow one.
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('quantity', '<=', 'low_stock_threshold');
    }
}
