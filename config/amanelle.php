<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Locales
    |---------------------------------------------------------------------------
    |
    | Arabic leads because the audience does: the Instagram bio, every caption
    | and the two markets are Arabic-speaking. English is the alternate, not
    | the default. `rtl_locales` drives the <html dir> attribute.
    |
    */

    'locales' => [
        'ar' => ['name' => 'العربية', 'dir' => 'rtl'],
        'en' => ['name' => 'English', 'dir' => 'ltr'],
    ],

    'rtl_locales' => ['ar'],

    /*
    |---------------------------------------------------------------------------
    | Markets
    |---------------------------------------------------------------------------
    |
    | Amanelle sells in Lebanon only. Saudi Arabia is where the stock comes
    | from, not somewhere we ship to — that is brands.origin_country, a
    | separate thing. Stock is still keyed by market so adding a second one
    | later does not need a migration.
    |
    */

    'markets' => [
        'LB' => ['name' => 'لبنان', 'currency' => 'USD', 'default' => true],
    ],

    'default_market' => 'LB',

    /*
    |---------------------------------------------------------------------------
    | Reservation window
    |---------------------------------------------------------------------------
    |
    | Hours a pending order may hold stock before `stock:release-expired` puts
    | the units back on sale. Stock is reserved when an order is placed and
    | only deducted from the shelf count on delivery, so without a window one
    | abandoned checkout removes an item from sale indefinitely.
    |
    */

    'reservation_hours' => 48,

    /*
    |---------------------------------------------------------------------------
    | Product types
    |---------------------------------------------------------------------------
    |
    | One variant system, many configurations. Each type declares which axes a
    | variant may vary along, and the admin builds the variant form from that.
    |
    | This is the single source of truth. It used to be inferred by matching
    | category names against a list of words in two languages, which meant a
    | category named anything unexpected silently lost its size field.
    |
    | Axes:
    |   volume        — size in ml
    |   concentration — EDT / EDP / Extrait, fragrance only
    |   shade         — a named colour with a hex swatch
    |
    | Types can combine axes: a foundation varies by shade and by bottle size,
    | so it gets both. To add a type, add an entry here — `type` is a plain
    | string column, so no migration is needed.
    |
    */

    'product_types' => [
        'fragrance' => ['label' => 'Fragrance', 'axes' => ['volume', 'concentration']],
        'skincare' => ['label' => 'Skincare', 'axes' => ['volume']],
        'makeup' => ['label' => 'Makeup', 'axes' => ['shade']],
        'makeup_sized' => ['label' => 'Makeup (shade and size)', 'axes' => ['shade', 'volume']],
        'haircare' => ['label' => 'Hair care', 'axes' => ['volume']],
        'bodycare' => ['label' => 'Body care', 'axes' => ['volume']],
        'accessory' => ['label' => 'Accessory', 'axes' => []],
        'gift_set' => ['label' => 'Gift set', 'axes' => ['volume']],
    ],

    /*
    |---------------------------------------------------------------------------
    | Social profiles
    |---------------------------------------------------------------------------
    |
    | Only Instagram is confirmed — on amanelle.store all four icons currently
    | point at "#", so the other three destinations are unknown. Fill them in
    | here and the footer picks them up; leave one null and its icon is
    | dropped rather than rendered as a dead link.
    |
    */

    'socials' => [
        'instagram' => 'https://www.instagram.com/amanelle_beauty',
        'facebook' => null,
        'tiktok' => null,
        'pinterest' => null,
    ],

];
