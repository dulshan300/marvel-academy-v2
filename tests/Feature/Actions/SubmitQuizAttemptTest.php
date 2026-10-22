<?php

namespace Tests\Feature\Actions;

use App\Actions\Quizzes\StartQuizAttempt;
use App\Actions\Quizzes\SubmitQuizAttempt;
use App\Models\Child;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Notifications\QuizResultNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Tests\TestCase;

class SubmitQuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A quiz with three one-mark questions and their correct option IDs.
     *
     * @return array{Quiz, array<int, int>, array<int, int>}
     */
    private function quizWithAnswers(int $passMark = 60): array
    {
        $quiz = Quiz::factory()->create(['pass_mark' => $passMark]);
        $correct = [];
        $wrong = [];

        foreach (range(1, 3) as $position) {
            $question = Question::factory()->for($quiz)->create(['position' => $position]);
            $correct[$question->id] = QuestionOption::factory()->correct()->for($question)->create()->id;
            $wrong[$question->id] = QuestionOption::factory()->for($question)->create()->id;
        }

        return [$quiz, $correct, $wrong];
    }

    public function test_all_correct_answers_score_full_marks_and_pass(): void
    {
        Notification::fake();
        [$quiz, $correct] = $this->quizWithAnswers();
        $attempt = app(StartQuizAttempt::class)->handle(Child::factory()->create(), $quiz);

        app(SubmitQuizAttempt::class)->handle($attempt, $correct);

        $attempt->refresh();
        $this->assertSame(3, $attempt->score);
        $this->assertSame(3, $attempt->max_score);
        $this->assertSame(100, $attempt->percentage);
        $this->assertTrue($attempt->passed);
        $this->assertNotNull($attempt->completed_at);
    }

    public function test_score_below_pass_mark_fails(): void
    {
        Notification::fake();
        [$quiz, $correct, $wrong] = $this->quizWithAnswers(passMark: 70);
        $attempt = app(StartQuizAttempt::class)->handle(Child::factory()->create(), $quiz);
        $questionIds = array_keys($correct);

        app(SubmitQuizAttempt::class)->handle($attempt, [
            $questionIds[0] => $correct[$questionIds[0]],
            $questionIds[1] => $correct[$questionIds[1]],
            $questionIds[2] => $wrong[$questionIds[2]],
        ]);

        $attempt->refresh();
        $this->assertSame(2, $attempt->score);
        $this->assertSame(67, $attempt->percentage);
        $this->assertFalse($attempt->passed);
    }

    public function test_unanswered_questions_and_options_from_other_questions_score_zero(): void
    {
        Notification::fake();
        [$quiz, $correct] = $this->quizWithAnswers();
        $attempt = app(StartQuizAttempt::class)->handle(Child::factory()->create(), $quiz);
        $questionIds = array_keys($correct);

        app(SubmitQuizAttempt::class)->handle($attempt, [
            $questionIds[0] => $correct[$questionIds[1]],
        ]);

        $attempt->refresh();
        $this->assertSame(0, $attempt->score);
        $this->assertCount(3, $attempt->answers);
        $this->assertNull($attempt->answers->firstWhere('question_id', $questionIds[0])->question_option_id);
    }

    public function test_marks_are_weighted_per_question(): void
    {
        Notification::fake();
        $quiz = Quiz::factory()->create();
        $hard = Question::factory()->for($quiz)->create(['marks' => 3]);
        $easy = Question::factory()->for($quiz)->create(['marks' => 1]);
        $hardCorrect = QuestionOption::factory()->correct()->for($hard)->create();
        QuestionOption::factory()->correct()->for($easy)->create();
        $attempt = app(StartQuizAttempt::class)->handle(Child::factory()->create(), $quiz);

        app(SubmitQuizAttempt::class)->handle($attempt, [$hard->id => $hardCorrect->id]);

        $this->assertSame(3, $attempt->fresh()->score);
        $this->assertSame(75, $attempt->fresh()->percentage);
    }

    public function test_parent_is_emailed_the_result(): void
    {
        Notification::fake();
        [$quiz, $correct] = $this->quizWithAnswers();
        $child = Child::factory()->create();
        $attempt = app(StartQuizAttempt::class)->handle($child, $quiz);

        app(SubmitQuizAttempt::class)->handle($attempt, $correct);

        Notification::assertSentTo($child->parent, QuizResultNotification::class, fn ($notification) => $notification->attempt->is($attempt));
        $this->assertNotNull($attempt->fresh()->parent_notified_at);
    }

    public function test_result_email_shows_the_score(): void
    {
        [$quiz, $correct] = $this->quizWithAnswers();
        $child = Child::factory()->create(['first_name' => 'Maya']);
        $attempt = app(StartQuizAttempt::class)->handle($child, $quiz);
        Notification::fake();
        app(SubmitQuizAttempt::class)->handle($attempt, $correct);

        $mail = (new QuizResultNotification($attempt->fresh()))->toMail($child->parent);

        $this->assertStringContainsString('Maya scored 100%', $mail->subject);
        $this->assertStringContainsString('3 / 3', (string) $mail->render());
    }

    public function test_submitting_twice_is_refused(): void
    {
        Notification::fake();
        [$quiz, $correct] = $this->quizWithAnswers();
        $attempt = app(StartQuizAttempt::class)->handle(Child::factory()->create(), $quiz);
        app(SubmitQuizAttempt::class)->handle($attempt, $correct);

        $this->expectException(LogicException::class);

        app(SubmitQuizAttempt::class)->handle($attempt->fresh(), []);
    }

    public function test_start_resumes_an_unfinished_attempt(): void
    {
        [$quiz] = $this->quizWithAnswers();
        $child = Child::factory()->create();

        $first = app(StartQuizAttempt::class)->handle($child, $quiz);
        $second = app(StartQuizAttempt::class)->handle($child, $quiz);

        $this->assertTrue($first->is($second));
    }

    public function test_start_replaces_an_attempt_past_its_time_limit(): void
    {
        $quiz = Quiz::factory()->create(['time_limit_minutes' => 10]);
        $child = Child::factory()->create();
        $first = app(StartQuizAttempt::class)->handle($child, $quiz);

        $this->travel(11)->minutes();

        $second = app(StartQuizAttempt::class)->handle($child, $quiz);

        $this->assertFalse($first->is($second));
    }
}
