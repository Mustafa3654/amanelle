<?php

namespace App\Support;

/**
 * The product type catalogue.
 *
 * Both the product form and the variant form ask this class what a type
 * means, so they cannot drift apart — previously the variant form guessed
 * from category names and disagreed with the type actually stored.
 */
class ProductTypes
{
    /** @return array<string, array{label: string, axes: array<int, string>}> */
    public static function all(): array
    {
        return config('amanelle.product_types', []);
    }

    /** Options for a select, translated. @return array<string, string> */
    public static function options(): array
    {
        return collect(static::all())
            ->map(fn (array $type) => __($type['label']))
            ->all();
    }

    /** @return array<int, string> */
    public static function axesFor(?string $type): array
    {
        return static::all()[$type]['axes'] ?? [];
    }

    public static function hasAxis(?string $type, string $axis): bool
    {
        return in_array($axis, static::axesFor($type), true);
    }

    public static function label(?string $type): string
    {
        return __(static::all()[$type]['label'] ?? (string) $type);
    }

    /** Validation rule for the stored value. */
    public static function rule(): string
    {
        return 'in:'.implode(',', array_keys(static::all()));
    }
}
