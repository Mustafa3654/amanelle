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
    | Product types
    |---------------------------------------------------------------------------
    |
    | One variant system, three configurations. Fragrance dominates the
    | catalogue, so it is not a special case bolted onto a makeup schema —
    | each type simply declares which variant axes apply to it.
    |
    */

    'product_types' => [
        'fragrance' => ['axes' => ['volume', 'concentration']],
        'skincare' => ['axes' => ['volume']],
        'makeup' => ['axes' => ['shade']],
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
