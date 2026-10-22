<?php

namespace Database\Factories;

use App\Enums\KeyStage;
use App\Models\Child;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $yearGroup = fake()->numberBetween(7, 11);

        return [
            'parent_id' => User::factory()->parent(),
            'student_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => now()->subYears($yearGroup + 5)->subMonths(fake()->numberBetween(0, 11))->toDateString(),
            'key_stage' => $yearGroup <= 9 ? KeyStage::Ks3 : KeyStage::Gcse,
            'year_group' => $yearGroup,
            'school_name' => fake()->city().' Academy',
        ];
    }

    /**
     * Give the child a student portal login.
     */
    public function withStudentAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => User::factory()->student(),
        ]);
    }
}
