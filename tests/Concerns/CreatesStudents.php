<?php

namespace Tests\Concerns;

use App\Billing\LocalBillingGateway;
use App\Enums\EnrolmentStatus;
use App\Enums\KeyStage;
use App\Models\Child;
use App\Models\Course;
use App\Models\Plan;

trait CreatesStudents
{
    /**
     * A GCSE student with a student login, enrolled on the course and on an
     * active plan (or no plan when $subscribed is false).
     */
    protected function enrolledStudent(Course $course, bool $subscribed = true): Child
    {
        $plan = Plan::factory()->create(['subjects_included' => 4, 'trial_days' => 0]);

        $child = Child::factory()->withStudentAccount()->create([
            'key_stage' => KeyStage::Gcse,
            'year_group' => 10,
            'plan_id' => $plan->id,
        ]);

        $child->enrolments()->create([
            'course_id' => $course->id,
            'status' => EnrolmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        if ($subscribed) {
            (new LocalBillingGateway)->startSubscription($child, $plan, null, '/', '/');
        }

        return $child->fresh();
    }
}
