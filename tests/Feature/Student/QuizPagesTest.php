<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class QuizPagesTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private function quizInCourse(Course $course): Quiz
    {
        $topic = Topic::factory()->for(Subject::factory()->for($course))->create();

        return Quiz::factory()->withQuestions(2)->for($topic)->create()->load('questions.options');
    }

    public function test_enrolled_student_can_take_and_submit_a_quiz(): void
    {
        Notification::fake();
        $course = Course::factory()->create();
        $quiz = $this->quizInCourse($course);
        $child = $this->enrolledStudent($course);
        $answers = $quiz->questions->mapWithKeys(fn ($question) => [$question->id => $question->options->firstWhere('is_correct', true)->id])->all();

        $component = Livewire::actingAs($child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $quiz])
            ->assertSee($quiz->questions->first()->prompt)
            ->set('answers', $answers)
            ->call('submit');

        $attempt = $child->quizAttempts()->sole();
        $component->assertRedirect(route('student.quizzes.result', [$quiz, $attempt]));
        $this->assertSame(100, $attempt->percentage);
    }

    public function test_quiz_page_does_not_reveal_correct_answers(): void
    {
        $course = Course::factory()->create();
        $quiz = $this->quizInCourse($course);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->student)
            ->get(route('student.quizzes.take', $quiz))
            ->assertOk()
            ->assertDontSee('is_correct');
    }

    public function test_incomplete_submission_is_rejected_when_untimed(): void
    {
        $course = Course::factory()->create();
        $quiz = $this->quizInCourse($course);
        $child = $this->enrolledStudent($course);

        Livewire::actingAs($child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $quiz])
            ->call('submit')
            ->assertHasErrors('answers');

        $this->assertNull($child->quizAttempts()->sole()->completed_at);
    }

    public function test_student_without_an_active_plan_is_forbidden(): void
    {
        $course = Course::factory()->create();
        $quiz = $this->quizInCourse($course);
        $child = $this->enrolledStudent($course, subscribed: false);

        $this->actingAs($child->student)
            ->get(route('student.quizzes.take', $quiz))
            ->assertForbidden();
    }

    public function test_student_not_enrolled_on_the_course_is_forbidden(): void
    {
        $quiz = $this->quizInCourse(Course::factory()->create());
        $child = $this->enrolledStudent(Course::factory()->create());

        $this->actingAs($child->student)
            ->get(route('student.quizzes.take', $quiz))
            ->assertForbidden();
    }

    public function test_unpublished_quiz_is_forbidden(): void
    {
        $course = Course::factory()->create();
        $quiz = $this->quizInCourse($course);
        $quiz->update(['is_published' => false]);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->student)
            ->get(route('student.quizzes.take', $quiz))
            ->assertForbidden();
    }

    public function test_result_page_shows_score_and_explanations_to_the_student_and_parent(): void
    {
        Notification::fake();
        $course = Course::factory()->create();
        $quiz = $this->quizInCourse($course);
        $child = $this->enrolledStudent($course);

        Livewire::actingAs($child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $quiz])
            ->set('answers', $quiz->questions->mapWithKeys(fn ($question) => [$question->id => $question->options->first()->id])->all())
            ->call('submit');

        $attempt = $child->quizAttempts()->sole();
        $explanation = $quiz->questions->first()->explanation;

        $this->actingAs($child->student)
            ->get(route('student.quizzes.result', [$quiz, $attempt]))
            ->assertOk()
            ->assertSee("{$attempt->percentage}%")
            ->assertSee($explanation);
    }

    public function test_another_student_cannot_view_someone_elses_result(): void
    {
        Notification::fake();
        $course = Course::factory()->create();
        $quiz = $this->quizInCourse($course);
        $child = $this->enrolledStudent($course);
        $other = $this->enrolledStudent($course);

        Livewire::actingAs($child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $quiz])
            ->set('answers', $quiz->questions->mapWithKeys(fn ($question) => [$question->id => $question->options->first()->id])->all())
            ->call('submit');

        $this->actingAs($other->student)
            ->get(route('student.quizzes.result', [$quiz, $child->quizAttempts()->sole()]))
            ->assertForbidden();
    }
}
