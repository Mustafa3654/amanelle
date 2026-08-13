<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Turns storefront filter/sort input into a query.
 *
 * Kept out of the views so the shop page, category pages and search all
 * behave identically — a filter that works in one place and not another is
 * worse than no filter.
 */
class ProductQuery
{
    public const SORTS = [
        'featured' => 'Featured',
        'popular' => 'Most popular',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'newest' => 'Newest',
    ];

    /**
     * Accepts a relation as well as a builder: category pages pass
     * $category->products(), which is a HasMany. It forwards every scope call
     * to the underlying builder, so only the type hint has to be wide enough.
     */
    public static function apply(Builder|Relation $query, array $filters): Builder|Relation
    {
        $query->active()
            ->with(['brand', 'variants', 'references'])
            ->withLowestPrice();

        $query->search($filters['q'] ?? null);

        if (! empty($filters['brand'])) {
            $query->whereHas('brand', fn (Builder $b) => $b->where('slug', $filters['brand']));
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // "At least this good" rather than an exact match: someone filtering
        // for long-lasting wants 4 and 5, not only 4.
        if (! empty($filters['longevity'])) {
            $query->where('longevity', '>=', (int) $filters['longevity']);
        }

        if (! empty($filters['projection'])) {
            $query->where('projection', '>=', (int) $filters['projection']);
        }

        if (! empty($filters['inspired'])) {
            $query->whereHas('references');
        }

        // Price bounds are in the base currency, matching how prices are
        // stored; the form converts what the customer typed before it gets
        // here.
        // Scoped to active variants, like the sort. A product carrying an
        // inactive variant — a placeholder awaiting a price, or a discontinued
        // size — must not match on a price the shop will never sell it at.
        if (! empty($filters['min_price'])) {
            $query->whereHas('variants', fn (Builder $v) => $v
                ->where('is_active', true)
                ->where('price', '>=', (float) $filters['min_price']));
        }

        if (! empty($filters['max_price'])) {
            $query->whereHas('variants', fn (Builder $v) => $v
                ->where('is_active', true)
                ->where('price', '<=', (float) $filters['max_price']));
        }

        return static::sort($query, $filters['sort'] ?? 'featured');
    }

    private static function sort(Builder|Relation $query, string $sort): Builder|Relation
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('lowest_price'),
            'price_desc' => $query->orderByDesc('lowest_price'),
            'newest' => $query->latest('published_at'),
            'popular' => $query->withPopularity()
                // Nulls last: never-sold products should not lead the list on
                // a "most popular" sort just because their sum is null.
                ->orderByRaw('COALESCE(units_sold, 0) DESC')
                ->latest('published_at'),
            default => $query->orderByDesc('is_featured')->latest('published_at'),
        };
    }

    /** @return array<string, mixed> */
    public static function fromRequest(\Illuminate\Http\Request $request): array
    {
        return $request->only([
            'q', 'brand', 'gender', 'type', 'longevity', 'projection',
            'inspired', 'min_price', 'max_price', 'sort',
        ]);
    }

    public static function isFiltered(array $filters): bool
    {
        return collect($filters)
            ->except('sort')
            ->filter(fn ($v) => filled($v))
            ->isNotEmpty();
    }
}
