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
            'gallery' => 'array',
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
     * Free-text search across every translation plus the brand and SKU.
     *
     * LIKE rather than MATCH: the fulltext index exists, but MySQL fulltext
     * tokenises on whitespace and ignores short words, so "Bell" would miss
     * "Match Bell" partials and Arabic words with attached prefixes would not
     * match at all. At this catalogue size LIKE is both correct and fast.
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('search_text', 'like', $like)
                ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like))
                ->orWhereHas('variants', fn (Builder $v) => $v->where('sku', 'like', $like))
                // Someone searching "Kayali" wants the alternative to it —
                // that journey is the account's whole pitch.
                ->orWhereHas('references', fn (Builder $r) => $r
                    ->where('designer_house', 'like', $like)
                    ->orWhere('original_name', 'like', $like));
        });
    }

    /**
     * Popularity is units actually delivered, not orders placed: pending
     * orders can still be cancelled, and counting them would let anyone
     * inflate a ranking by filling a cart.
     */
    public function scopeWithPopularity(Builder $query): void
    {
        $query->withSum(
            ['orderItems as units_sold' => fn ($q) => $q->whereHas(
                'order',
                fn ($o) => $o->where('status', 'delivered')
            )],
            'quantity'
        );
    }

    public function orderItems(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            OrderItem::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id',
        );
    }

    /** Cheapest active variant price, for sorting a listing by price. */
    public function scopeWithLowestPrice(Builder $query): void
    {
        $query->withMin(['variants as lowest_price' => fn ($q) => $q->where('is_active', true)], 'price');
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

    /**
     * The image to show for this product, preferring a variant's own shot.
     *
     * A lipstick photographs per shade; a perfume needs one bottle picture for
     * every size. Falling back keeps both cases in one call.
     */
    public function displayImage(?ProductVariant $variant = null): ?string
    {
        return $variant?->image_path
            ?? $this->image_path
            ?? $this->variants->firstWhere('image_path', '!=', null)?->image_path;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
