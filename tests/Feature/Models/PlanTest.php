<?php

namespace Tests\Feature\Models;

use App\Models\Plan;
use Tests\TestCase;

class PlanTest extends TestCase
{
    public function test_hourly_rate_divides_monthly_price_by_included_hours(): void
    {
        $plan = Plan::factory()->make(['price_monthly' => 17900, 'hours_per_month' => 8]);

        $this->assertSame(2238, $plan->hourlyRate());
    }

    public function test_hourly_rate_is_zero_when_no_hours_are_included(): void
    {
        $plan = Plan::factory()->make(['hours_per_month' => 0]);

        $this->assertSame(0, $plan->hourlyRate());
    }
}
