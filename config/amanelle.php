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
    | Amanelle sources from Saudi Arabia and sells into Lebanon (the bio carries
    | both flags; captions reference buying perfume in Lebanon). Lebanon
    | transacts in USD in practice, so LB is priced in USD rather than LBP.
    |
    */

    'markets' => [
        'SA' => ['name' => 'السعودية', 'currency' => 'SAR', 'default' => true],
        'LB' => ['name' => 'لبنان', 'currency' => 'USD', 'default' => false],
    ],

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

];
