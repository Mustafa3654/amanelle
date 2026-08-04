<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FragranceReference extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'original_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function displayName(): string
    {
        return "{$this->designer_house} {$this->original_name}";
    }

    /**
     * What the customer saves against the designer original. Null when we have
     * no verified reference price — better to show nothing than a number we
     * cannot stand behind, given the brand sells on trust.
     */
    public function savingAgainst(float $ourPrice): ?float
    {
        if (! $this->original_price || $this->original_price <= $ourPrice) {
            return null;
        }

        return round((float) $this->original_price - $ourPrice, 2);
    }
}
