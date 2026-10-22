<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

class QuizPolicy
{
    /**
     * Take a published quiz in a course the student can access, for their school year.
     */
    public function attempt(User $user, Quiz $quiz): bool
    {
        return $quiz->is_published
            && ($user->studentProfile?->canAccessTopic($quiz->topic) ?? false);
    }

    /**
     * See a completed attempt's results: the student who took it or their parent.
     */
    public function viewAttempt(User $user, Quiz $quiz, QuizAttempt $attempt): bool
    {
        if ($attempt->quiz_id !== $quiz->id || ! $attempt->isComplete()) {
            return false;
        }

        $child = $attempt->child;

        return $child->student_id === $user->id || $child->parent_id === $user->id;
    }
}
