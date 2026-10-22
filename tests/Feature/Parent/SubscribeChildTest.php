<?php

namespace Tests\Feature\Parent;

use App\Billing\BillingGateway;
use App\Enums\KeyStage;
use App\Models\Child;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class SubscribeChildTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{User, Child, Plan, Course}
     */
    private function arrange(): array
    {
        $parent = User::factory()->parent()->create();
        $child = Child::factory()->for($parent, 'parent')->create(['key_stage' => KeyStage::Gcse, 'year_group' => 10]);
        $plan = Plan::factory()->create(['slug' => 'one-subject', 'subjects_included' => 1, 'price_monthly' => 1999, 'trial_days' => 7]);
        $course = Course::factory()->keyStage(KeyStage::Gcse)->create();

        return [$parent, $child, $plan, $course];
    }

    public function test_checkout_starts_a_trial_subscription_and_enrols_the_child(): void
    {
        [$parent, $child, $plan, $course] = $this->arrange();

        Livewire::actingAs($parent)
            ->test('pages::parent.children.subscribe', ['child' => $child])
            ->set('planSlug', $plan->slug)
            ->set('courseIds', [(string) $course->id])
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertRedirect(route('parent.checkout.success', $child));

        $child->refresh();

        $this->assertTrue($child->hasActiveSubscription());
        $this->assertTrue($child->subscription()->onTrial());
        $this->assertTrue($child->plan->is($plan));
        $this->assertEquals([$course->id], $child->activeCourses()->pluck('courses.id')->all());
    }

    public function test_valid_coupon_is_passed_to_the_billing_provider(): void
    {
        [$parent, $child, $plan, $course] = $this->arrange();
        $coupon = Coupon::factory()->create(['code' => 'SIBLING10', 'percent_off' => 10]);

        $this->mock(BillingGateway::class, function (MockInterface $mock) use ($child, $plan, $coupon): void {
            $mock->shouldReceive('startSubscription')
                ->once()
                ->withArgs(fn ($givenChild, $givenPlan, $givenCoupon) => $givenChild->is($child) && $givenPlan->is($plan) && $givenCoupon?->is($coupon))
                ->andReturn('https://checkout.stripe.test/session');
        });

        Livewire::actingAs($parent)
            ->test('pages::parent.children.subscribe', ['child' => $child])
            ->set('planSlug', $plan->slug)
            ->set('courseIds', [$course->id])
            ->set('couponCode', 'sibling10 ')
            ->call('applyCoupon')
            ->assertHasNoErrors()
            ->assertSet('appliedCouponCode', 'SIBLING10')
            ->assertSee('−£2')
            ->call('checkout')
            ->assertRedirect('https://checkout.stripe.test/session');
    }

    public function test_expired_coupon_is_rejected(): void
    {
        [$parent, $child] = $this->arrange();
        Coupon::factory()->expired()->create(['code' => 'OLDCODE']);

        Livewire::actingAs($parent)
            ->test('pages::parent.children.subscribe', ['child' => $child])
            ->set('couponCode', 'OLDCODE')
            ->call('applyCoupon')
            ->assertHasErrors('couponCode')
            ->assertSet('appliedCouponCode', null);
    }

    public function test_unknown_coupon_is_rejected(): void
    {
        [$parent, $child] = $this->arrange();

        Livewire::actingAs($parent)
            ->test('pages::parent.children.subscribe', ['child' => $child])
            ->set('couponCode', 'NOPE')
            ->call('applyCoupon')
            ->assertHasErrors('couponCode');
    }

    public function test_local_checkout_counts_the_coupon_redemption(): void
    {
        [$parent, $child, $plan, $course] = $this->arrange();
        $coupon = Coupon::factory()->create(['code' => 'WELCOME']);

        Livewire::actingAs($parent)
            ->test('pages::parent.children.subscribe', ['child' => $child])
            ->set('planSlug', $plan->slug)
            ->set('courseIds', [$course->id])
            ->set('couponCode', 'WELCOME')
            ->call('applyCoupon')
            ->call('checkout');

        $this->assertSame(1, $coupon->fresh()->times_redeemed);
    }

    public function test_too_many_courses_for_the_plan_is_rejected(): void
    {
        [$parent, $child, $plan, $course] = $this->arrange();
        $second = Course::factory()->keyStage(KeyStage::Gcse)->create();

        Livewire::actingAs($parent)
            ->test('pages::parent.children.subscribe', ['child' => $child])
            ->set('planSlug', $plan->slug)
            ->set('courseIds', [$course->id, $second->id])
            ->call('checkout')
            ->assertHasErrors('courses');

        $this->assertFalse($child->fresh()->hasActiveSubscription());
    }

    public function test_changing_to_a_smaller_plan_trims_the_selection(): void
    {
        [$parent, $child, $plan, $course] = $this->arrange();
        $twoSubjects = Plan::factory()->create(['slug' => 'two-subjects', 'subjects_included' => 2]);
        $second = Course::factory()->keyStage(KeyStage::Gcse)->create();

        Livewire::actingAs($parent)
            ->test('pages::parent.children.subscribe', ['child' => $child])
            ->set('planSlug', $twoSubjects->slug)
            ->set('courseIds', [$course->id, $second->id])
            ->set('planSlug', $plan->slug)
            ->assertCount('courseIds', 1);
    }

    public function test_parent_cannot_subscribe_another_familys_child(): void
    {
        [, $child] = $this->arrange();

        $this->actingAs(User::factory()->parent()->create())
            ->get(route('parent.children.subscribe', $child))
            ->assertForbidden();
    }

    public function test_child_with_an_active_plan_is_sent_to_course_management(): void
    {
        [$parent, $child, $plan, $course] = $this->arrange();
        app(BillingGateway::class)->startSubscription($child, $plan, null, '/', '/');
        $child->plan()->associate($plan)->save();

        Livewire::actingAs($parent)
            ->test('pages::parent.children.subscribe', ['child' => $child])
            ->assertRedirect(route('parent.children.courses', $child));
    }

    public function test_checkout_success_page_forbids_other_parents(): void
    {
        [, $child] = $this->arrange();

        $this->actingAs(User::factory()->parent()->create())
            ->get(route('parent.checkout.success', $child))
            ->assertForbidden();
    }
}
