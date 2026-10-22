<?php

namespace App\Actions\Quizzes;

use App\Models\Child;
use App\Models\Quiz;
use App\Models\QuizAttempt;

class StartQuizAttempt
{
    public function __construct(private SubmitQuizAttempt $submitAttempt) {}

    /**
     * Resume the child's unfinished attempt at the quiz, or begin a new one.
     *
     * A timed attempt whose time ran out while the child was away is marked
     * with the answers saved so far and returned complete, so the caller can
     * show its result instead of silently discarding the work.
     */
    public function handle(Child $child, Quiz $quiz): QuizAttempt
    {
        $inProgress = $quiz->attempts()
            ->whereBelongsTo($child)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();

        if ($inProgress !== null) {
            if (! $inProgress->hasExpired(SaveQuizAnswers::GRACE_SECONDS)) {
                return $inProgress;
            }

            return $this->submitAttempt->handle($inProgress, $inProgress->savedAnswers());
        }

        return $quiz->attempts()->create([
            'child_id' => $child->id,
            'max_score' => $quiz->questions()->sum('marks'),
            'started_at' => now(),
        ]);
    }
}
