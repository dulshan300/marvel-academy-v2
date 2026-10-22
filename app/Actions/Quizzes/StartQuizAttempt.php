<?php

namespace App\Actions\Quizzes;

use App\Models\Child;
use App\Models\Quiz;
use App\Models\QuizAttempt;

class StartQuizAttempt
{
    /**
     * Resume the child's unfinished attempt at the quiz, or begin a new one.
     */
    public function handle(Child $child, Quiz $quiz): QuizAttempt
    {
        $inProgress = $quiz->attempts()
            ->whereBelongsTo($child)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();

        if ($inProgress !== null && ! $this->hasExpired($inProgress, $quiz)) {
            return $inProgress;
        }

        return $quiz->attempts()->create([
            'child_id' => $child->id,
            'max_score' => $quiz->questions()->sum('marks'),
            'started_at' => now(),
        ]);
    }

    private function hasExpired(QuizAttempt $attempt, Quiz $quiz): bool
    {
        return $quiz->time_limit_minutes !== null
            && $attempt->started_at->copy()->addMinutes($quiz->time_limit_minutes)->isPast();
    }
}
