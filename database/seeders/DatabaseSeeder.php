<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate:fresh --seed
     *
     * Creates settings, Murchison Falls National Park (places, UWA-priced activities, gallery, 360° scenes, travel updates), the admin,
     * demo tourists/operators with packages, and demo analytics data.
     * Set SEED_DEMO_DATA=false in .env to skip the demo bookings/reviews/views.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            ParkSeeder::class,
            ParkVideoSeeder::class,
            UserSeeder::class,
            TravelUpdateSeeder::class,
        ]);

        if (env('SEED_DEMO_DATA', true)) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
