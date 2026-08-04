<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        /*
         * USD is the base: Lebanon prices and transacts in dollars in
         * practice, and every product price in the catalogue is stored in it.
         *
         * The LBP rate is a starting value, not a fact — it moves, and it is
         * editable in the admin precisely so nobody has to ship a deploy to
         * change it.
         */
        Currency::updateOrCreate(['code' => 'USD'], [
            'name' => ['en' => 'US Dollar', 'ar' => 'دولار أمريكي'],
            'symbol' => '$',
            'rate' => 1,
            'decimals' => 2,
            'is_base' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Currency::updateOrCreate(['code' => 'LBP'], [
            'name' => ['en' => 'Lebanese Pound', 'ar' => 'ليرة لبنانية'],
            'symbol' => 'ل.ل',
            'rate' => 89500,
            // Whole pounds only; decimals on a number this size are noise.
            'decimals' => 0,
            'is_base' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Setting::put('base_currency', 'USD');
        Setting::put('default_display_currency', 'USD');
    }
}
