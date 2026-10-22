<?php

namespace Database\Factories;

use App\Mail\SessionPreparationReminder;
use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mailable' => SessionPreparationReminder::class,
            'subject' => fake()->sentence(5),
            'recipients' => fake()->safeEmail(),
            'message_id' => fake()->uuid().'@marvelacademy.test',
            'sent_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }
}
