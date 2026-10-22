<?php

namespace App\Policies;

use App\Enums\LiveSessionStatus;
use App\Models\LiveSession;
use App\Models\User;

class LiveSessionPolicy
{
    /**
     * Watch the stream or recording of a session in an accessible course.
     */
    public function watch(User $user, LiveSession $session): bool
    {
        return $session->status !== LiveSessionStatus::Cancelled
            && ($session->teacher_id === $user->id
                || ($user->studentProfile?->canAccessCourse($session->course_id) ?? false));
    }

    /**
     * Rate the tutor after a completed session the student attended.
     */
    public function rate(User $user, LiveSession $session): bool
    {
        $child = $user->studentProfile;

        return $child !== null
            && $session->teacher_id !== null
            && $session->starts_at->isPast()
            && $session->attendances()->where('child_id', $child->id)->exists()
            && ! $session->ratings()->where('rated_by_id', $user->id)->exists();
    }
}
