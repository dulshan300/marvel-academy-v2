<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    /**
     * Download a course material: enrolled students, the parent, and course staff.
     */
    public function download(User $user, Material $material): bool
    {
        $courseId = $material->topic->courseId();

        if ($user->can(Permission::ManageCourses->value)) {
            return true;
        }

        if ($user->taughtCourses()->whereKey($courseId)->exists()) {
            return true;
        }

        if ($user->studentProfile?->canAccessCourse($courseId)) {
            return true;
        }

        return $user->children()->get()->contains(fn ($child) => $child->canAccessCourse($courseId));
    }
}
