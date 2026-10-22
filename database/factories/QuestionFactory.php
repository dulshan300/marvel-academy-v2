<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'prompt' => rtrim(fake()->sentence(10), '.').'?',
            'explanation' => fake()->sentence(),
            'marks' => 1,
            'position' => 0,
        ];
    }

    /**
     * A picture question; attach the image with addMedia(...)->toMediaCollection('image').
     */
    public function picture(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::Picture,
            'description' => fake()->sentence(12),
        ]);
    }
}
