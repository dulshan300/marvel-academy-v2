<?php

namespace App\Enums;

/**
 * Fine-grained staff permissions. The admin role holds all of them; custom
 * staff roles can be granted a subset from the role management screen.
 */
enum Permission: string
{
    case ManageCourses = 'manage courses';
    case ManageQuizzes = 'manage quizzes';
    case ManageLiveSessions = 'manage live sessions';
    case ManageFamilies = 'manage families';
    case ManageTeachers = 'manage teachers';
    case ManagePayments = 'manage payments';
    case ManageEmails = 'manage emails';
    case ManageContent = 'manage content';
    case ManageRoles = 'manage roles';
    case ManageBackups = 'manage backups';
    case ViewAnalytics = 'view analytics';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
