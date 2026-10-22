<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'name' => fake()->words(2, true),
            'percent_off' => 10,
            'amount_off' => null,
            'duration' => 'once',
            'max_redemptions' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function amountOff(int $pence): static
    {
        return $this->state(fn (array $attributes) => [
            'percent_off' => null,
            'amount_off' => $pence,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
