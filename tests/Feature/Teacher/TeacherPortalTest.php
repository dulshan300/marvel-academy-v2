<?php

namespace Tests\Feature\Teacher;

use App\Enums\LiveSessionStatus;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\Rating;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class TeacherPortalTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private function teacher(): User
    {
        return TeacherProfile::factory()->create()->user;
    }

    public function test_dashboard_shows_own_sessions_and_feedback(): void
    {
        $teacher = $this->teacher();
        LiveSession::factory()->startingIn(3)->create(['teacher_id' => $teacher->id, 'title' => 'My algebra session']);
        LiveSession::factory()->startingIn(3)->create(['title' => 'Someone else session']);
        Rating::factory()->create(['teacher_id' => $teacher->id, 'comment' => 'Brilliant explanations']);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('My algebra session')
            ->assertDontSee('Someone else session')
            ->assertSee('Brilliant explanations');
    }

    public function test_students_page_lists_students_on_taught_courses_only(): void
    {
        $teacher = $this->teacher();
        $course = Course::factory()->create();
        $course->teachers()->attach($teacher);
        $mine = $this->enrolledStudent($course);
        $other = $this->enrolledStudent(Course::factory()->create());

        $this->actingAs($teacher)
            ->get(route('teacher.students'))
            ->assertOk()
            ->assertSee($mine->full_name)
            ->assertDontSee($other->full_name);
    }

    public function test_teacher_adds_a_recording_and_completes_their_session(): void
    {
        $teacher = $this->teacher();
        $session = LiveSession::factory()->create(['teacher_id' => $teacher->id, 'starts_at' => now()->subHours(3)]);

        Livewire::actingAs($teacher)
            ->test('pages::teacher.sessions.show', ['session' => $session])
            ->set('recording_url', 'https://www.youtube.com/watch?v=abcdefghijk')
            ->call('saveRecording')
            ->assertHasNoErrors()
            ->call('markCompleted');

        $session->refresh();
        $this->assertSame('https://www.youtube.com/watch?v=abcdefghijk', $session->recording_url);
        $this->assertSame(LiveSessionStatus::Completed, $session->status);
    }

    public function test_teacher_cannot_manage_another_teachers_session(): void
    {
        $session = LiveSession::factory()->create();

        $this->actingAs($this->teacher())
            ->get(route('teacher.sessions.show', $session))
            ->assertForbidden();
    }

    public function test_session_roster_shows_who_joined(): void
    {
        $teacher = $this->teacher();
        $course = Course::factory()->create();
        $session = LiveSession::factory()->for($course)->create(['teacher_id' => $teacher->id, 'starts_at' => now()->subHour()]);
        $joined = $this->enrolledStudent($course);
        $this->enrolledStudent($course);
        $session->attendances()->create(['child_id' => $joined->id, 'joined_at' => now()->subHour()]);

        $this->actingAs($teacher)
            ->get(route('teacher.sessions.show', $session))
            ->assertOk()
            ->assertSee('1/2 joined');
    }

    public function test_teacher_updates_profile_and_avatar(): void
    {
        Storage::fake('public');
        $teacher = $this->teacher();

        Livewire::actingAs($teacher)
            ->test('pages::teacher.profile')
            ->set('headline', 'GCSE Physics Tutor')
            ->set('years_experience', 8)
            ->set('avatar', UploadedFile::fake()->image('me.png', 400, 400))
            ->call('save')
            ->assertHasNoErrors();

        $profile = $teacher->teacherProfile->fresh();
        $this->assertSame('GCSE Physics Tutor', $profile->headline);
        $this->assertSame(8, $profile->years_experience);
        $this->assertNotNull($profile->getFirstMedia('avatar'));
    }

    public function test_students_cannot_open_the_teacher_portal(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('teacher.dashboard'))
            ->assertForbidden();
    }
}
