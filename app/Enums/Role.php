<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Parent = 'parent';
    case Student = 'student';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * The named route each role lands on after signing in.
     */
    public function dashboardRoute(): string
    {
        return $this->value.'.dashboard';
    }
}
