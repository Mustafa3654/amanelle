<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

/**
 * Starting fees — adjust them under Settings → Delivery areas.
 */
class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        DeliveryZone::updateOrCreate(
            ['id' => 1],
            [
                'name' => ['en' => 'Beirut', 'ar' => 'بيروت'],
                'description' => ['en' => 'Within the city', 'ar' => 'داخل المدينة'],
                'fee' => 2,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        DeliveryZone::updateOrCreate(
            ['id' => 2],
            [
                'name' => ['en' => 'Outside Beirut', 'ar' => 'خارج بيروت'],
                'description' => ['en' => 'Everywhere else in Lebanon', 'ar' => 'باقي المناطق اللبنانية'],
                'fee' => 3,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }
}
