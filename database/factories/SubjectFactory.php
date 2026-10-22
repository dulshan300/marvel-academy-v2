<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
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
            'name' => fake()->randomElement(['Number', 'Algebra', 'Geometry', 'Statistics', 'Reading', 'Writing', 'Cells', 'Forces']),
            'description' => fake()->sentence(),
            'position' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Limit the subject to one school year.
     */
    public function forYear(int $yearGroup): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "Year {$yearGroup}",
            'year_group' => $yearGroup,
        ]);
    }
}
