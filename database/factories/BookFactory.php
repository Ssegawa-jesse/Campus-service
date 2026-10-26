<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'isbn' => fake()->isbn13(),
            'publisher' => fake()->company(),
            'year' => fake()->numberBetween(1995, 2025),
        ];
    }
}
