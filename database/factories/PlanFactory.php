<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word()).' Plan';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price_monthly' => 9900,
            'subjects_included' => 1,
            'hours_per_month' => 4,
            'trial_days' => 7,
            'features' => ['Weekly live lessons', 'Topic quizzes'],
            'stripe_price_id' => 'price_'.Str::random(14),
            'is_featured' => false,
            'is_active' => true,
            'position' => 0,
        ];
    }
}
