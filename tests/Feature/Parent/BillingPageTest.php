<?php

namespace Tests\Feature\Parent;

use App\Actions\Children\AssignCourses;
use App\Billing\BillingGateway;
use App\Enums\KeyStage;
use App\Models\Child;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A parent with one child on a two-subject plan with two courses.
     *
     * @return array{User, Child, Plan}
     */
    private function subscribedFamily(): array
    {
        $parent = User::factory()->parent()->create();
        $child = Child::factory()->for($parent, 'parent')->create(['key_stage' => KeyStage::Gcse, 'year_group' => 10]);
        $plan = Plan::factory()->create(['slug' => 'two-subjects', 'name' => '2 Subjects', 'subjects_included' => 2, 'trial_days' => 0]);
        $courses = Course::factory()->count(2)->keyStage(KeyStage::Gcse)->create();

        app(AssignCourses::class)->handle($child, $plan, $courses->pluck('id')->all());
        app(BillingGateway::class)->startSubscription($child, $plan, null, '/', '/');

        return [$parent, $child->fresh(), $plan];
    }

    public function test_page_lists_each_child_with_their_plan(): void
    {
        [$parent, $child] = $this->subscribedFamily();

        $this->actingAs($parent)
            ->get(route('parent.billing'))
            ->assertOk()
            ->assertSee($child->full_name)
            ->assertSee('2 Subjects')
            ->assertSee('Active');
    }

    public function test_cancel_keeps_access_until_the_period_ends_and_can_be_resumed(): void
    {
        [$parent, $child] = $this->subscribedFamily();

        Livewire::actingAs($parent)->test('pages::parent.billing')->call('cancel', $child->ulid);

        $subscription = $child->fresh()->subscription();
        $this->assertTrue($subscription->onGracePeriod());
        $this->assertTrue($child->fresh()->hasActiveSubscription());

        Livewire::actingAs($parent)->test('pages::parent.billing')->call('resume', $child->ulid);

        $this->assertFalse($child->fresh()->subscription()->onGracePeriod());
    }

    public function test_changing_to_a_plan_with_fewer_subjects_than_active_courses_is_blocked(): void
    {
        [$parent, $child, $plan] = $this->subscribedFamily();
        $oneSubject = Plan::factory()->create(['slug' => 'one-subject', 'subjects_included' => 1]);

        Livewire::actingAs($parent)
            ->test('pages::parent.billing')
            ->set("newPlans.{$child->ulid}", $oneSubject->slug)
            ->call('changePlan', $child->ulid)
            ->assertHasErrors("newPlans.{$child->ulid}");

        $this->assertTrue($child->fresh()->plan->is($plan));
    }

    public function test_changing_to_a_larger_plan_updates_the_child(): void
    {
        [$parent, $child] = $this->subscribedFamily();
        $threeSubjects = Plan::factory()->create(['slug' => 'three-subjects', 'subjects_included' => 3]);

        Livewire::actingAs($parent)
            ->test('pages::parent.billing')
            ->set("newPlans.{$child->ulid}", $threeSubjects->slug)
            ->call('changePlan', $child->ulid)
            ->assertHasNoErrors();

        $this->assertTrue($child->fresh()->plan->is($threeSubjects));
    }

    public function test_applying_a_valid_coupon_records_a_redemption(): void
    {
        [$parent, $child] = $this->subscribedFamily();
        $coupon = Coupon::factory()->create(['code' => 'SPRING']);

        Livewire::actingAs($parent)
            ->test('pages::parent.billing')
            ->set("couponCodes.{$child->ulid}", 'spring')
            ->call('applyCoupon', $child->ulid)
            ->assertHasNoErrors();

        $this->assertSame(1, $coupon->fresh()->times_redeemed);
    }

    public function test_parent_cannot_cancel_another_familys_plan(): void
    {
        [, $child] = $this->subscribedFamily();

        Livewire::actingAs(User::factory()->parent()->create())
            ->test('pages::parent.billing')
            ->call('cancel', $child->ulid)
            ->assertForbidden();

        $this->assertFalse($child->fresh()->subscription()->onGracePeriod());
    }
}
