<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
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
            'child_id' => Child::factory(),
            'score' => 0,
            'max_score' => 0,
            'percentage' => 0,
            'passed' => false,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    public function completed(int $score = 4, int $maxScore = 5, int $passMark = 60): static
    {
        $percentage = $maxScore > 0 ? (int) round($score / $maxScore * 100) : 0;

        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'passed' => $percentage >= $passMark,
            'started_at' => now()->subMinutes(15),
            'completed_at' => now(),
        ]);
    }
}
