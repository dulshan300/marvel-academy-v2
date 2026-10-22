<?php

namespace Tests\Feature\Parent;

use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_parent_sees_onboarding_steps(): void
    {
        $this->actingAs(User::factory()->parent()->create())
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee("Let's get your first child set up", escape: false);
    }

    public function test_dashboard_lists_only_the_parents_own_children(): void
    {
        $parent = User::factory()->parent()->create();
        Child::factory()->count(2)->for($parent, 'parent')->sequence(['first_name' => 'Maya'], ['first_name' => 'Leo'])->create();
        Child::factory()->create(['first_name' => 'Stranger']);

        $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('Maya')
            ->assertSee('Leo')
            ->assertSee('Choose a plan')
            ->assertDontSee('Stranger');
    }
}
