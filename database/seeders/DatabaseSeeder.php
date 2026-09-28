<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate:fresh --seed
     *
     * Creates settings, all 10 parks (with activities, gallery & 360° scenes), the admin,
     * demo tourists/operators with packages, and demo analytics data.
     * Set SEED_DEMO_DATA=false in .env to skip the demo bookings/reviews/views.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            ParkSeeder::class,
            UserSeeder::class,
        ]);

        if (env('SEED_DEMO_DATA', true)) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
