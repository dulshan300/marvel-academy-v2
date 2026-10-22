<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;

class QuestionPolicy
{
    /**
     * See a picture question's image: course staff, students who can take the
     * quiz, and the student or parent of a child who has attempted it.
     */
    public function viewImage(User $user, Question $question): bool
    {
        $topic = $question->loadMissing('quiz.topic')->quiz->topic;

        if ($user->can(Permission::ManageCourses->value)) {
            return true;
        }

        if ($user->taughtCourses()->whereKey($topic->courseId())->exists()) {
            return true;
        }

        if ($user->studentProfile?->canAccessTopic($topic)) {
            return true;
        }

        return QuizAttempt::where('quiz_id', $question->quiz_id)
            ->whereHas('child', fn ($query) => $query->where('student_id', $user->id)->orWhere('parent_id', $user->id))
            ->exists();
    }
}
