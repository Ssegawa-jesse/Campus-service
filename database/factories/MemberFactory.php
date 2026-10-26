<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'member_no' => fake()->unique()->numerify('24007#####'),
            'type' => 'student',
            'programme' => 'Bachelor of Business Computing',
            'expiry_date' => now()->addYear(),
        ];
    }

    public function staff(): static
    {
        return $this->state(fn () => ['type' => 'staff']);
    }
}
