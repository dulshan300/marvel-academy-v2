<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'rated_by_id' => User::factory()->parent(),
            'live_session_id' => null,
            'score' => fake()->numberBetween(3, 5),
            'comment' => fake()->sentence(),
        ];
    }
}
