<?php

namespace Database\Factories;

use App\Enums\EnrolmentStatus;
use App\Models\Child;
use App\Models\Course;
use App\Models\Enrolment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrolment>
 */
class EnrolmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'course_id' => Course::factory(),
            'status' => EnrolmentStatus::Active,
            'enrolled_at' => now(),
        ];
    }
}
