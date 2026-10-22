<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => null,
            'author_name' => fake()->firstName().' '.fake()->lastName()[0].'.',
            'author_role' => fake()->randomElement(['Parent of Year 8 student', 'Year 11 student', 'Parent of Year 10 student', 'Year 9 student']),
            'content' => fake()->paragraph(),
            'rating' => fake()->numberBetween(4, 5),
            'is_verified' => true,
            'is_published' => true,
            'position' => 0,
        ];
    }
}
