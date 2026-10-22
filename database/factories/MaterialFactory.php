<?php

namespace Database\Factories;

use App\Enums\MaterialType;
use App\Models\Material;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
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
            'title' => ucfirst(fake()->words(4, true)),
            'description' => fake()->sentence(),
            'type' => MaterialType::Link,
            'external_url' => fake()->url(),
            'is_preparatory' => false,
            'position' => fake()->numberBetween(0, 10),
        ];
    }

    public function preparatory(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_preparatory' => true,
        ]);
    }
}
