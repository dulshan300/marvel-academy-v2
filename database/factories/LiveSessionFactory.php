<?php

namespace Database\Factories;

use App\Enums\LiveSessionStatus;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSession>
 */
class LiveSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'topic_id' => null,
            'teacher_id' => User::factory()->teacher(),
            'title' => 'Live: '.ucfirst(fake()->words(3, true)),
            'description' => fake()->paragraph(),
            'starts_at' => now()->addDays(fake()->numberBetween(1, 30))->setTime(17, 0),
            'duration_minutes' => 60,
            'stream_url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9_-]{11}'),
            'status' => LiveSessionStatus::Scheduled,
        ];
    }

    public function startingIn(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addDays($days),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'status' => LiveSessionStatus::Completed,
            'recording_url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9_-]{11}'),
        ]);
    }
}
