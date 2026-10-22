<?php

namespace Database\Factories;

use App\Enums\KeyStage;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjectArea = fake()->randomElement(['Mathematics', 'English', 'Science', 'Biology', 'Chemistry', 'Physics', 'History', 'Geography']);

        return [
            'title' => $subjectArea.' '.fake()->unique()->numberBetween(1, 100000),
            'key_stage' => fake()->randomElement([KeyStage::Ks3, KeyStage::Gcse]),
            'subject_area' => $subjectArea,
            'summary' => fake()->sentence(15),
            'description' => fake()->paragraphs(3, true),
            'price_per_hour' => fake()->randomElement([2500, 3000, 3500]),
            'price_monthly' => fake()->randomElement([8900, 10900, 12900]),
            'hours_per_month' => 4,
            'is_featured' => false,
            'is_published' => true,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }

    public function keyStage(KeyStage $keyStage): static
    {
        return $this->state(fn (array $attributes) => [
            'key_stage' => $keyStage,
        ]);
    }
}
