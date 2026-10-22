<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\LiveSession;
use App\Models\Material;
use App\Models\Rating;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    public function test_dashboard_lists_enrolled_courses_and_upcoming_sessions(): void
    {
        $course = Course::factory()->create(['title' => 'Combined Science']);
        $child = $this->enrolledStudent($course);
        LiveSession::factory()->for($course)->startingIn(3)->create(['title' => 'Atomic structure live']);
        LiveSession::factory()->startingIn(3)->create(['title' => 'Some other course']);

        $this->actingAs($child->student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Combined Science')
            ->assertSee('Atomic structure live')
            ->assertDontSee('Some other course');
    }

    public function test_dashboard_warns_when_the_plan_is_inactive(): void
    {
        $child = $this->enrolledStudent(Course::factory()->create(), subscribed: false);

        $this->actingAs($child->student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Your lessons are paused');
    }

    public function test_course_page_shows_topics_and_materials_to_enrolled_students(): void
    {
        $course = Course::factory()->create();
        $topic = Topic::factory()->for(Subject::factory()->for($course))->create(['title' => 'Electrolysis']);
        Material::factory()->for($topic)->create(['title' => 'Electrolysis question pack']);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->student)
            ->get(route('student.courses.show', $course))
            ->assertOk()
            ->assertSee('Electrolysis')
            ->assertSee('Electrolysis question pack');
    }

    public function test_course_page_is_forbidden_without_enrolment(): void
    {
        $child = $this->enrolledStudent(Course::factory()->create());

        $this->actingAs($child->student)
            ->get(route('student.courses.show', Course::factory()->create()))
            ->assertForbidden();
    }

    public function test_link_material_redirects_enrolled_student(): void
    {
        $course = Course::factory()->create();
        $material = Material::factory()->for(Topic::factory()->for(Subject::factory()->for($course)))->create(['external_url' => 'https://example.com/pack']);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->student)
            ->get(route('materials.download', $material))
            ->assertRedirect('https://example.com/pack');
    }

    public function test_material_is_forbidden_to_students_outside_the_course(): void
    {
        $material = Material::factory()->create();
        $child = $this->enrolledStudent(Course::factory()->create());

        $this->actingAs($child->student)
            ->get(route('materials.download', $material))
            ->assertForbidden();
    }

    public function test_opening_a_live_session_records_attendance_once(): void
    {
        $course = Course::factory()->create();
        $session = LiveSession::factory()->for($course)->create(['starts_at' => now()->subMinutes(5)]);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->student)->get(route('student.sessions.show', $session))->assertOk();
        $this->actingAs($child->student)->get(route('student.sessions.show', $session))->assertOk();

        $this->assertSame(1, $session->attendances()->where('child_id', $child->id)->count());
    }

    public function test_youtube_stream_is_embedded_while_live(): void
    {
        $course = Course::factory()->create();
        $session = LiveSession::factory()->for($course)->create([
            'starts_at' => now()->subMinutes(5),
            'stream_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ]);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->student)
            ->get(route('student.sessions.show', $session))
            ->assertSee('https://www.youtube-nocookie.com/embed/abcdefghijk', escape: false);
    }

    public function test_future_session_does_not_record_attendance_or_show_the_stream(): void
    {
        $course = Course::factory()->create();
        $session = LiveSession::factory()->for($course)->startingIn(2)->create(['stream_url' => 'https://teams.microsoft.com/l/meetup-join/secret']);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->student)
            ->get(route('student.sessions.show', $session))
            ->assertOk()
            ->assertDontSee('teams.microsoft.com');

        $this->assertSame(0, $session->attendances()->count());
    }

    public function test_attendee_can_rate_the_tutor_once_and_aggregates_update(): void
    {
        $course = Course::factory()->create();
        $teacher = TeacherProfile::factory()->create()->user;
        $session = LiveSession::factory()->for($course)->create(['teacher_id' => $teacher->id, 'starts_at' => now()->subHours(2)]);
        $child = $this->enrolledStudent($course);
        $session->attendances()->create(['child_id' => $child->id, 'joined_at' => now()->subHours(2)]);

        Livewire::actingAs($child->student)
            ->test('pages::student.sessions.show', ['session' => $session])
            ->set('score', 4)
            ->set('comment', 'Very clear explanations')
            ->call('rate')
            ->assertHasNoErrors()
            ->assertSet('rated', true);

        $this->assertSame(4, Rating::sole()->score);
        $this->assertEquals(4.0, (float) $teacher->teacherProfile->fresh()->rating_average);
        $this->assertSame(1, $course->fresh()->rating_count);

        Livewire::actingAs($child->student)
            ->test('pages::student.sessions.show', ['session' => $session])
            ->set('score', 5)
            ->call('rate')
            ->assertForbidden();
    }

    public function test_student_who_did_not_attend_cannot_rate(): void
    {
        $course = Course::factory()->create();
        $session = LiveSession::factory()->for($course)->create(['starts_at' => now()->subHours(2)]);
        $child = $this->enrolledStudent($course);

        Livewire::actingAs($child->student)
            ->test('pages::student.sessions.show', ['session' => $session])
            ->set('score', 5)
            ->call('rate')
            ->assertForbidden();
    }

    public function test_timetable_shows_this_months_sessions(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(10));
        $course = Course::factory()->create();
        LiveSession::factory()->for($course)->startingIn(2)->create(['title' => 'Waves revision']);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->student)
            ->get(route('student.timetable'))
            ->assertOk()
            ->assertSee('Waves revision');
    }

    public function test_parents_cannot_open_the_student_portal(): void
    {
        $this->actingAs(User::factory()->parent()->create())
            ->get(route('student.dashboard'))
            ->assertForbidden();
    }
}
