<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'title' => ucfirst(fake()->words(3, true)).' Quiz',
            'description' => fake()->sentence(),
            'pass_mark' => 60,
            'time_limit_minutes' => null,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }

    /**
     * Add questions that each have four options, exactly one of them correct.
     */
    public function withQuestions(int $count = 5): static
    {
        return $this->afterCreating(function (Quiz $quiz) use ($count): void {
            Question::factory()
                ->count($count)
                ->sequence(fn ($sequence) => ['position' => $sequence->index])
                ->for($quiz)
                ->create()
                ->each(function (Question $question): void {
                    $correctPosition = fake()->numberBetween(0, 3);

                    foreach (range(0, 3) as $position) {
                        QuestionOption::factory()->for($question)->create([
                            'position' => $position,
                            'is_correct' => $position === $correctPosition,
                        ]);
                    }
                });
        });
    }
}
