<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Child;
use App\Models\User;

class ChildPolicy
{
    public function view(User $user, Child $child): bool
    {
        return $this->ownsChild($user, $child)
            || $child->student_id === $user->id
            || $user->can(Permission::ManageFamilies->value);
    }

    /**
     * Managing courses, plans and billing for the child.
     */
    public function update(User $user, Child $child): bool
    {
        return $this->ownsChild($user, $child) || $user->can(Permission::ManageFamilies->value);
    }

    private function ownsChild(User $user, Child $child): bool
    {
        return $child->parent_id === $user->id;
    }
}
