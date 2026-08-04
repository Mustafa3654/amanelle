<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = [];

    public array $translatable = ['name', 'short_description', 'description'];

    protected function casts(): array
    {
        return [
            'notes_top' => 'array',
            'notes_heart' => 'array',
            'notes_base' => 'array',
            'skin_types' => 'array',
            'concerns' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Flatten every translation into one indexed column so an Arabic and
        // an English search both hit the same fulltext index.
        static::saving(function (self $product) {
            $product->search_text = collect($product->getTranslations('name'))
                ->merge($product->getTranslations('short_description'))
                ->filter()
                ->implode(' ');
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function references(): HasMany
    {
        return $this->hasMany(FragranceReference::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * The cheapest active variant — what a listing card shows as "from".
     */
    public function cheapestVariant(): ?ProductVariant
    {
        return $this->variants
            ->where('is_active', true)
            ->sortBy('price')
            ->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
