<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Open the course in the student portal.
     */
    public function study(User $user, Course $course): bool
    {
        return $user->studentProfile?->canAccessCourse($course->id) ?? false;
    }

    public function manage(User $user): bool
    {
        return $user->can(Permission::ManageCourses->value);
    }
}
