<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    /**
     * Download a course material: enrolled students in the topic's school year, their parent, and course staff.
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

        if ($user->studentProfile?->canAccessTopic($material->topic)) {
            return true;
        }

        return $user->children()->get()->contains(fn ($child) => $child->canAccessTopic($material->topic));
    }
}
