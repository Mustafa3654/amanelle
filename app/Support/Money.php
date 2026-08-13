<?php

namespace App\Support;

use App\Models\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * All prices are stored in the base currency (USD). Everything a customer sees
 * is converted at display time from an admin-editable rate, so changing the
 * LBP rate reprices the catalogue without touching a single product row —
 * which matters when the rate can move week to week.
 */
class Money
{
    public const SESSION_KEY = 'currency';

    private const CACHE_KEY = 'amanelle.currencies';

    /**
     * Memoised for the life of one request, not cached across them. There are
     * two rows; the query is trivial, and serialising Eloquent models into the
     * cache store brings them back as __PHP_Incomplete_Class the moment the
     * class map shifts.
     *
     * Held in the container rather than a static property: a static survives
     * for the whole PHP process, which in the test suite means one test's
     * currencies leak into the next and failures depend on run order. The
     * container is rebuilt per request and per test, so this dies when it
     * should.
     *
     * @return Collection<int, Currency>
     */
    public static function currencies(): Collection
    {
        if (! app()->bound(self::CACHE_KEY)) {
            app()->instance(self::CACHE_KEY, Currency::active()->orderBy('sort_order')->get());
        }

        return app(self::CACHE_KEY);
    }

    public static function base(): ?Currency
    {
        return static::currencies()->firstWhere('is_base', true);
    }

    /**
     * The currency the customer is browsing in — their session choice, else
     * the base.
     */
    public static function current(): ?Currency
    {
        $code = session(self::SESSION_KEY);

        return static::currencies()->firstWhere('code', $code) ?? static::base();
    }

    /**
     * Returns markup (a <bdi> wrapper), so it must be echoed unescaped.
     * See Currency::format for why the isolation is necessary.
     */
    public static function format(?float $baseAmount): HtmlString
    {
        if ($baseAmount === null) {
            return new HtmlString('');
        }

        return new HtmlString(
            static::current()?->format($baseAmount) ?? number_format($baseAmount, 2)
        );
    }

    /** Plain text version, for anywhere markup would be wrong. */
    public static function plain(?float $baseAmount): string
    {
        if ($baseAmount === null) {
            return '';
        }

        return static::current()?->formatPlain($baseAmount) ?? number_format($baseAmount, 2);
    }

    public static function flush(): void
    {
        app()->forgetInstance(self::CACHE_KEY);
    }
}
