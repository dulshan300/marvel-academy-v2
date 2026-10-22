<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\LiveSession;
use App\Models\SessionAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionAttendance>
 */
class SessionAttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'live_session_id' => LiveSession::factory(),
            'child_id' => Child::factory(),
            'joined_at' => now(),
        ];
    }
}
