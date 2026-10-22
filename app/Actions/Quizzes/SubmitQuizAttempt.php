<?php

namespace App\Actions\Quizzes;

use App\Models\Question;
use App\Models\QuizAttempt;
use App\Notifications\QuizResultNotification;
use Illuminate\Support\Facades\DB;
use LogicException;

class SubmitQuizAttempt
{
    /**
     * Grade the attempt, store each answer and notify the parent.
     *
     * Unanswered questions, and options that do not belong to the question,
     * score zero.
     *
     * @param  array<int|string, int|string|null>  $answers  question ID => selected option ID
     */
    public function handle(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        if ($attempt->isComplete()) {
            throw new LogicException('This quiz attempt has already been submitted.');
        }

        $questions = $attempt->quiz->questions()->with('options')->get();

        DB::transaction(function () use ($attempt, $answers, $questions): void {
            $score = 0;

            foreach ($questions as $question) {
                /** @var Question $question */
                $selectedId = isset($answers[$question->id]) ? (int) $answers[$question->id] : null;
                $selected = $question->options->firstWhere('id', $selectedId);
                $isCorrect = $selected?->is_correct === true;
                $marks = $isCorrect ? $question->marks : 0;

                $score += $marks;

                $attempt->answers()->updateOrCreate(
                    ['question_id' => $question->id],
                    [
                        'question_option_id' => $selected?->id,
                        'is_correct' => $isCorrect,
                        'marks_awarded' => $marks,
                    ],
                );
            }

            $maxScore = (int) $questions->sum('marks');
            $percentage = $maxScore > 0 ? (int) round($score / $maxScore * 100) : 0;

            $attempt->forceFill([
                'score' => $score,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'passed' => $percentage >= $attempt->quiz->pass_mark,
                'completed_at' => now(),
            ])->save();
        });

        $this->notifyParent($attempt);

        return $attempt;
    }

    private function notifyParent(QuizAttempt $attempt): void
    {
        $parent = $attempt->child->parent;

        if ($parent === null) {
            return;
        }

        $parent->notify(new QuizResultNotification($attempt));

        $attempt->forceFill(['parent_notified_at' => now()])->save();
    }
}
