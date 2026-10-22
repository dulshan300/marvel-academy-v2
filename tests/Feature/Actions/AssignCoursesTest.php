<?php

namespace Tests\Feature\Actions;

use App\Actions\Children\AssignCourses;
use App\Enums\EnrolmentStatus;
use App\Enums\KeyStage;
use App\Models\Child;
use App\Models\Course;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssignCoursesTest extends TestCase
{
    use RefreshDatabase;

    private function gcseChild(): Child
    {
        return Child::factory()->create(['key_stage' => KeyStage::Gcse, 'year_group' => 10]);
    }

    public function test_enrols_child_and_records_the_plan(): void
    {
        $child = $this->gcseChild();
        $plan = Plan::factory()->create(['subjects_included' => 2]);
        $courses = Course::factory()->count(2)->keyStage(KeyStage::Gcse)->create();

        app(AssignCourses::class)->handle($child, $plan, $courses->pluck('id')->all());

        $this->assertTrue($child->fresh()->plan->is($plan));
        $this->assertEqualsCanonicalizing($courses->pluck('id')->all(), $child->activeCourses()->pluck('courses.id')->all());
    }

    public function test_rejects_more_courses_than_the_plan_includes(): void
    {
        $child = $this->gcseChild();
        $plan = Plan::factory()->create(['subjects_included' => 1, 'name' => 'Starter']);
        $courses = Course::factory()->count(2)->keyStage(KeyStage::Gcse)->create();

        $this->expectException(ValidationException::class);

        try {
            app(AssignCourses::class)->handle($child, $plan, $courses->pluck('id')->all());
        } finally {
            $this->assertDatabaseCount('enrolments', 0);
        }
    }

    public function test_rejects_courses_from_another_key_stage(): void
    {
        $child = $this->gcseChild();
        $plan = Plan::factory()->create(['subjects_included' => 2]);
        $ks3Course = Course::factory()->keyStage(KeyStage::Ks3)->create();

        $this->expectException(ValidationException::class);

        app(AssignCourses::class)->handle($child, $plan, [$ks3Course->id]);
    }

    public function test_rejects_unpublished_courses(): void
    {
        $child = $this->gcseChild();
        $plan = Plan::factory()->create(['subjects_included' => 2]);
        $draft = Course::factory()->keyStage(KeyStage::Gcse)->unpublished()->create();

        $this->expectException(ValidationException::class);

        app(AssignCourses::class)->handle($child, $plan, [$draft->id]);
    }

    public function test_rejects_an_empty_selection(): void
    {
        $this->expectException(ValidationException::class);

        app(AssignCourses::class)->handle($this->gcseChild(), Plan::factory()->create(), []);
    }

    public function test_deselected_courses_are_cancelled_not_deleted(): void
    {
        $child = $this->gcseChild();
        $plan = Plan::factory()->create(['subjects_included' => 1]);
        [$maths, $physics] = Course::factory()->count(2)->keyStage(KeyStage::Gcse)->create();

        app(AssignCourses::class)->handle($child, $plan, [$maths->id]);
        app(AssignCourses::class)->handle($child, $plan, [$physics->id]);

        $this->assertSame(EnrolmentStatus::Cancelled, $child->enrolments()->where('course_id', $maths->id)->sole()->status);
        $this->assertEquals([$physics->id], $child->activeCourses()->pluck('courses.id')->all());
    }
}
