<?php

namespace Database\Factories;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherProfile>
 */
class TeacherProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->teacher(),
            'headline' => fake()->randomElement(['Head of Maths', 'GCSE Science Specialist', 'English Literature Tutor', 'Qualified Teacher (QTS)']),
            'bio' => fake()->paragraphs(2, true),
            'qualifications' => fake()->randomElement(['BSc Mathematics, PGCE', 'MSc Physics, QTS', 'BA English, PGCE', 'BSc Chemistry, QTS']),
            'years_experience' => fake()->numberBetween(2, 25),
            'dbs_checked' => true,
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
