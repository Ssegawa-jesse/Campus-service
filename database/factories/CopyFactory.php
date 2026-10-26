<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Copy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Copy>
 */
class CopyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'code' => 'BK-'.fake()->unique()->bothify('????####'),
            'shelf_location' => 'Shelf A1',
            'condition' => 'good',
            'status' => 'available',
        ];
    }
}
