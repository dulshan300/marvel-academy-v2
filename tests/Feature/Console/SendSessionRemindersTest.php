<?php

namespace Tests\Feature\Console;

use App\Actions\Sessions\SendSessionReminder;
use App\Enums\LiveSessionStatus;
use App\Enums\MaterialType;
use App\Mail\SessionJoiningReminder;
use App\Mail\SessionPreparationReminder;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\Material;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class SendSessionRemindersTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_seven_day_reminder_is_queued_to_parents_of_enrolled_students(): void
    {
        $course = Course::factory()->create();
        $session = LiveSession::factory()->for($course)->create(['starts_at' => now()->addDays(6)]);
        $child = $this->enrolledStudent($course);

        $this->artisan('sessions:send-reminders')->assertSuccessful();

        Mail::assertQueued(SessionPreparationReminder::class, fn ($mail) => $mail->hasTo($child->parent->email) && $mail->session->is($session));
        Mail::assertNotQueued(SessionJoiningReminder::class);
        $this->assertNotNull($session->fresh()->seven_day_reminder_sent_at);
    }

    public function test_two_day_reminder_is_queued_with_the_stream_link(): void
    {
        $course = Course::factory()->create();
        $session = LiveSession::factory()->for($course)->create([
            'starts_at' => now()->addDays(2)->subHour(),
            'stream_url' => 'https://teams.microsoft.com/l/meetup-join/lesson',
        ]);
        $child = $this->enrolledStudent($course);

        $this->artisan('sessions:send-reminders')->assertSuccessful();

        Mail::assertQueued(SessionJoiningReminder::class, function (SessionJoiningReminder $mail) use ($child): bool {
            return $mail->hasTo($child->parent->email)
                && str_contains($mail->render(), 'https://teams.microsoft.com/l/meetup-join/lesson');
        });
        $this->assertNotNull($session->fresh()->two_day_reminder_sent_at);
    }

    public function test_reminders_are_sent_only_once(): void
    {
        $course = Course::factory()->create();
        LiveSession::factory()->for($course)->create(['starts_at' => now()->addDays(6)]);
        $this->enrolledStudent($course);

        $this->artisan('sessions:send-reminders');
        $this->artisan('sessions:send-reminders');

        Mail::assertQueuedCount(1);
    }

    public function test_sessions_outside_the_windows_are_ignored(): void
    {
        $course = Course::factory()->create();
        LiveSession::factory()->for($course)->create(['starts_at' => now()->addDays(10)]);
        LiveSession::factory()->for($course)->create(['starts_at' => now()->subHour()]);
        $this->enrolledStudent($course);

        $this->artisan('sessions:send-reminders');

        Mail::assertNothingQueued();
    }

    public function test_cancelled_sessions_are_ignored(): void
    {
        $course = Course::factory()->create();
        LiveSession::factory()->for($course)->create(['starts_at' => now()->addDays(5), 'status' => LiveSessionStatus::Cancelled]);
        $this->enrolledStudent($course);

        $this->artisan('sessions:send-reminders');

        Mail::assertNothingQueued();
    }

    public function test_families_without_an_active_plan_or_enrolment_are_skipped(): void
    {
        $course = Course::factory()->create();
        LiveSession::factory()->for($course)->create(['starts_at' => now()->addDays(5)]);
        $lapsed = $this->enrolledStudent($course, subscribed: false);
        $elsewhere = $this->enrolledStudent(Course::factory()->create());
        $active = $this->enrolledStudent($course);

        $this->artisan('sessions:send-reminders');

        Mail::assertQueued(SessionPreparationReminder::class, fn ($mail) => $mail->hasTo($active->parent->email));
        Mail::assertNotQueued(SessionPreparationReminder::class, fn ($mail) => $mail->hasTo($lapsed->parent->email));
        Mail::assertNotQueued(SessionPreparationReminder::class, fn ($mail) => $mail->hasTo($elsewhere->parent->email));
    }

    public function test_short_notice_session_skips_straight_to_the_two_day_reminder(): void
    {
        $course = Course::factory()->create();
        LiveSession::factory()->for($course)->create(['starts_at' => now()->addDay()]);
        $this->enrolledStudent($course);

        $this->artisan('sessions:send-reminders');

        Mail::assertQueued(SessionJoiningReminder::class);
        Mail::assertNotQueued(SessionPreparationReminder::class);
    }

    public function test_preparation_email_attaches_preparatory_pdfs_and_links_the_rest(): void
    {
        Storage::fake('s3');
        $course = Course::factory()->create();
        $topic = Topic::factory()->for(Subject::factory()->for($course))->create();
        $studyPlan = Material::factory()->preparatory()->for($topic)->create(['type' => MaterialType::Pdf, 'title' => 'Study plan', 'external_url' => null]);
        $studyPlan->addMedia(UploadedFile::fake()->create('study-plan.pdf', 40, 'application/pdf'))->toMediaCollection('file');
        Material::factory()->preparatory()->for($topic)->create(['title' => 'Bitesize reading']);
        Material::factory()->for($topic)->create(['title' => 'Not for the reminder']);
        $session = LiveSession::factory()->for($course)->create(['topic_id' => $topic->id, 'starts_at' => now()->addDays(6)]);
        $child = $this->enrolledStudent($course);

        $mail = new SessionPreparationReminder($session, $child);

        $this->assertCount(1, $mail->attachments());
        $mail->assertSeeInHtml('Study plan');
        $mail->assertSeeInHtml('(attached)');
        $mail->assertSeeInHtml('Bitesize reading');
        $mail->assertDontSeeInHtml('Not for the reminder');
    }

    public function test_unknown_reminder_type_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(SendSessionReminder::class)->handle(LiveSession::factory()->create(), 'weekly');
    }
}
