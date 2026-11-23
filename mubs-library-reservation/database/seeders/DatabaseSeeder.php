<?php

namespace Database\Seeders;

use App\Models\Reservation;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Load two sample reservations so the list is not empty on first run.
     */
    public function run(): void
    {
        Reservation::create([
            'student_name' => 'Aisha Namuli',
            'registration_number' => '22/U/5432/PS',
            'programme' => 'Bachelor of Business Computing',
            'book_title' => 'Database System Concepts',
            'pickup_date' => now()->addDays(3)->toDateString(),
        ]);

        Reservation::create([
            'student_name' => 'Brian Okello',
            'registration_number' => '23/U/1187/EVE',
            'programme' => 'Bachelor of Commerce',
            'book_title' => 'Principles of Accounting',
            'pickup_date' => now()->addDays(5)->toDateString(),
        ]);
    }
}
