<?php

namespace Database\Factories;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_name' => fake()->name(),
            'registration_number' => fake()->numerify('2#/U/####/PS'),
            'programme' => fake()->randomElement(Reservation::PROGRAMMES),
            'book_title' => fake()->sentence(3),
            'pickup_date' => fake()->dateTimeBetween('today', '+2 weeks')->format('Y-m-d'),
        ];
    }
}
