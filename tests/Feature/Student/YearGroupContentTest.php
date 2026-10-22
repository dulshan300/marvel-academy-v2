<?php

namespace Tests\Feature\Student;

use App\Enums\KeyStage;
use App\Mail\SessionPreparationReminder;
use App\Models\Child;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

/**
 * Students (enrolledStudent() creates a Year 10 child) only see topics for their own school year.
 */
class YearGroupContentTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private Course $course;

    private Topic $yearTenTopic;

    private Topic $yearElevenTopic;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->keyStage(KeyStage::Gcse)->create();
        $this->yearTenTopic = Topic::factory()->for(Subject::factory()->forYear(10)->for($this->course))->create(['title' => 'Quadratic equations']);
        $this->yearElevenTopic = Topic::factory()->for(Subject::factory()->forYear(11)->for($this->course))->create(['title' => 'Circle theorems']);
        $this->child = $this->enrolledStudent($this->course);
    }

    public function test_course_page_shows_only_the_students_year_and_all_year_subjects(): void
    {
        Topic::factory()->for(Subject::factory()->for($this->course)->create(['name' => 'Exam skills', 'year_group' => null]))->create(['title' => 'Command words']);

        $this->actingAs($this->child->student)
            ->get(route('student.courses.show', $this->course))
            ->assertOk()
            ->assertSee('Quadratic equations')
            ->assertSee('Command words')
            ->assertDontSee('Circle theorems');
    }

    public function test_course_page_explains_when_there_is_nothing_for_the_students_year(): void
    {
        $this->child->update(['year_group' => 11]);
        $this->yearElevenTopic->subject->delete();

        $this->actingAs($this->child->student)
            ->get(route('student.courses.show', $this->course))
            ->assertOk()
            ->assertSee('There are no Year 11 topics in this course yet.')
            ->assertDontSee('Quadratic equations');
    }

    public function test_materials_from_another_year_cannot_be_opened(): void
    {
        $own = Material::factory()->for($this->yearTenTopic)->create(['external_url' => 'https://example.com/own']);
        $other = Material::factory()->for($this->yearElevenTopic)->create(['external_url' => 'https://example.com/other']);

        $this->actingAs($this->child->student)->get(route('materials.download', $own))->assertRedirect('https://example.com/own');
        $this->actingAs($this->child->student)->get(route('materials.download', $other))->assertForbidden();
        $this->actingAs($this->child->parent)->get(route('materials.download', $other))->assertForbidden();
    }

    public function test_quizzes_from_another_year_cannot_be_taken(): void
    {
        $own = Quiz::factory()->for($this->yearTenTopic)->withQuestions(2)->create();
        $other = Quiz::factory()->for($this->yearElevenTopic)->withQuestions(2)->create();

        $this->actingAs($this->child->student)->get(route('student.quizzes.take', $own))->assertOk();
        $this->actingAs($this->child->student)->get(route('student.quizzes.take', $other))->assertForbidden();
    }

    public function test_sessions_for_another_years_topic_are_hidden_and_cannot_be_opened(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(10));
        $own = LiveSession::factory()->for($this->course)->startingIn(2)->create(['topic_id' => $this->yearTenTopic->id, 'title' => 'Year ten live']);
        LiveSession::factory()->for($this->course)->startingIn(2)->create(['topic_id' => null, 'title' => 'Whole course live']);
        $other = LiveSession::factory()->for($this->course)->startingIn(2)->create(['topic_id' => $this->yearElevenTopic->id, 'title' => 'Year eleven live']);

        // The dashboard titles sessions by their topic when they have one.
        $this->actingAs($this->child->student)->get(route('student.dashboard'))
            ->assertSee('Quadratic equations')
            ->assertSee('Whole course live')
            ->assertDontSee('Circle theorems');
        $this->actingAs($this->child->student)->get(route('student.timetable'))
            ->assertSee('Year ten live')
            ->assertDontSee('Year eleven live');
        $this->actingAs($this->child->student)->get(route('student.sessions.show', $own))->assertOk();
        $this->actingAs($this->child->student)->get(route('student.sessions.show', $other))->assertForbidden();
    }

    public function test_session_reminders_go_only_to_families_in_the_topics_year(): void
    {
        Mail::fake();
        $yearEleven = $this->enrolledStudent($this->course);
        $yearEleven->update(['year_group' => 11]);
        LiveSession::factory()->for($this->course)->create(['topic_id' => $this->yearTenTopic->id, 'starts_at' => now()->addDays(6)]);

        $this->artisan('sessions:send-reminders')->assertSuccessful();

        Mail::assertQueued(SessionPreparationReminder::class, fn ($mail) => $mail->hasTo($this->child->parent->email));
        Mail::assertNotQueued(SessionPreparationReminder::class, fn ($mail) => $mail->hasTo($yearEleven->parent->email));
    }
}
