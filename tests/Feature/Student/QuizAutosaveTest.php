<?php

namespace Tests\Feature\Student;

use App\Models\Child;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class QuizAutosaveTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private Quiz $quiz;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $course = Course::factory()->create();
        $this->quiz = Quiz::factory()->withQuestions(3)
            ->for(Topic::factory()->for(Subject::factory()->for($course)))
            ->create()
            ->load('questions.options');
        $this->child = $this->enrolledStudent($course);
    }

    public function test_each_answer_is_saved_as_soon_as_it_is_chosen(): void
    {
        $question = $this->quiz->questions->first();
        $option = $question->options->first();

        Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz])
            ->call('saveAnswer', $question->id, $option->id)
            ->assertReturned(true);

        $answer = $this->child->quizAttempts()->sole()->answers()->sole();
        $this->assertSame($option->id, $answer->question_option_id);
        $this->assertSame(0, $answer->marks_awarded, 'Autosaved answers are not marked until submission.');
    }

    public function test_reopening_the_quiz_restores_saved_answers(): void
    {
        $question = $this->quiz->questions->first();
        $option = $question->options->last();

        Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz])
            ->call('saveAnswer', $question->id, $option->id);

        Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz])
            ->assertSet("answers.{$question->id}", $option->id)
            ->assertSee('Answered 1 of 3');

        $this->assertSame(1, $this->child->quizAttempts()->count());
    }

    public function test_answers_kept_offline_are_synced_together(): void
    {
        $answers = $this->correctAnswers();

        Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz])
            ->call('sync', $answers)
            ->assertSet('answers', $answers);

        $this->assertSame($answers, $this->child->quizAttempts()->sole()->savedAnswers());
    }

    public function test_options_from_another_question_or_quiz_are_not_saved(): void
    {
        [$first, $second] = $this->quiz->questions->take(2)->values()->all();
        $foreign = Question::factory()->hasOptions(2)->create()->load('options');

        Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz])
            ->call('sync', [
                $first->id => $second->options->first()->id,
                $foreign->id => $foreign->options->first()->id,
            ]);

        $this->assertSame([], $this->child->quizAttempts()->sole()->savedAnswers());
    }

    public function test_submit_marks_saved_answers_plus_any_still_pending(): void
    {
        $answers = $this->correctAnswers();
        $lastQuestionId = array_key_last($answers);
        $saved = array_diff_key($answers, [$lastQuestionId => true]);

        Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz])
            ->call('sync', $saved);

        // A fresh page load (e.g. after reconnecting) submitting the answer the browser kept.
        Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz])
            ->call('submit', [$lastQuestionId => $answers[$lastQuestionId]])
            ->assertHasNoErrors();

        $attempt = $this->child->quizAttempts()->sole();
        $this->assertTrue($attempt->isComplete());
        $this->assertSame(100, $attempt->percentage);
    }

    public function test_answers_cannot_be_changed_after_submission(): void
    {
        $answers = $this->correctAnswers();
        $component = Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz])
            ->call('sync', $answers)
            ->call('submit');

        $question = $this->quiz->questions->first();
        $wrong = $question->options->firstWhere('is_correct', false);
        $component->call('saveAnswer', $question->id, $wrong->id);

        $this->assertSame($answers[$question->id], $this->child->quizAttempts()->sole()->savedAnswers()[$question->id]);
    }

    public function test_timed_attempt_left_past_its_deadline_is_marked_on_return(): void
    {
        $this->quiz->update(['time_limit_minutes' => 5]);
        $answers = $this->correctAnswers();

        Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz])
            ->call('sync', array_slice($answers, 0, 1, true));

        $this->travel(10)->minutes();

        Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz])
            ->assertRedirect();

        $attempt = $this->child->quizAttempts()->sole();
        $this->assertTrue($attempt->isComplete());
        $this->assertSame(1, $attempt->score);
    }

    public function test_answers_arriving_after_the_deadline_are_ignored(): void
    {
        $this->quiz->update(['time_limit_minutes' => 5]);
        $question = $this->quiz->questions->first();

        $component = Livewire::actingAs($this->child->student)
            ->test('pages::student.quizzes.take', ['quiz' => $this->quiz]);

        $this->travel(6)->minutes();
        $component->call('saveAnswer', $question->id, $question->options->first()->id);

        $this->assertSame([], $this->child->quizAttempts()->sole()->savedAnswers());
    }

    /**
     * @return array<int, int>
     */
    private function correctAnswers(): array
    {
        return $this->quiz->questions
            ->mapWithKeys(fn (Question $question) => [$question->id => $question->options->firstWhere('is_correct', true)->id])
            ->all();
    }
}
