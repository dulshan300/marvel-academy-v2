<?php

namespace Tests\Feature\Admin;

use App\Enums\LiveSessionStatus;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LiveSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_schedules_a_session(): void
    {
        $course = Course::factory()->create();
        $teacher = TeacherProfile::factory()->create()->user;

        Livewire::actingAs($this->admin)
            ->test('pages::admin.sessions.edit')
            ->set('course_id', $course->id)
            ->set('teacher_id', $teacher->id)
            ->set('title', 'Electricity I')
            ->set('starts_at', '2030-03-02T10:00')
            ->set('duration_minutes', 150)
            ->set('stream_url', 'https://www.youtube.com/watch?v=abcdefghijk')
            ->call('save')
            ->assertHasNoErrors();

        $session = LiveSession::sole();
        $this->assertTrue($session->course->is($course));
        $this->assertSame('2030-03-02 10:00', $session->starts_at->format('Y-m-d H:i'));
        $this->assertSame(LiveSessionStatus::Scheduled, $session->status);
    }

    public function test_stream_links_must_be_https(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.sessions.edit')
            ->set('course_id', Course::factory()->create()->id)
            ->set('title', 'Insecure')
            ->set('stream_url', 'http://example.com/stream')
            ->call('save')
            ->assertHasErrors('stream_url');
    }

    public function test_rescheduling_resets_sent_reminders(): void
    {
        $session = LiveSession::factory()->startingIn(10)->create();
        $session->forceFill(['seven_day_reminder_sent_at' => now(), 'two_day_reminder_sent_at' => now()])->save();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.sessions.edit', ['session' => $session])
            ->set('starts_at', now()->addDays(20)->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $session->refresh();
        $this->assertNull($session->seven_day_reminder_sent_at);
        $this->assertNull($session->two_day_reminder_sent_at);
    }

    public function test_editing_other_details_keeps_reminder_history(): void
    {
        $session = LiveSession::factory()->startingIn(10)->create();
        $session->forceFill(['seven_day_reminder_sent_at' => now()])->save();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.sessions.edit', ['session' => $session])
            ->set('title', 'Renamed session')
            ->call('save');

        $this->assertNotNull($session->fresh()->seven_day_reminder_sent_at);
    }

    public function test_calendar_lists_sessions_in_the_selected_month(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(5));
        LiveSession::factory()->startingIn(3)->create(['title' => 'This month']);
        LiveSession::factory()->startingIn(70)->create(['title' => 'Much later']);

        $this->actingAs($this->admin)
            ->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertSee('This month')
            ->assertDontSee('Much later');
    }
}
