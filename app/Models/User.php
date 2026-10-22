<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\HasPublicUlid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, HasPublicUlid, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The user's primary role. Users hold exactly one portal role.
     */
    public function primaryRole(): ?Role
    {
        foreach (Role::cases() as $role) {
            if ($this->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin->value);
    }

    /**
     * Children registered by this user as a parent.
     *
     * @return HasMany<Child, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Child::class, 'parent_id');
    }

    /**
     * The child record linked to this user's student login.
     *
     * @return HasOne<Child, $this>
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(Child::class, 'student_id');
    }

    /**
     * @return HasOne<TeacherProfile, $this>
     */
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    /**
     * Courses this user teaches.
     *
     * @return BelongsToMany<Course, $this>
     */
    public function taughtCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_teacher');
    }

    /**
     * @return HasMany<LiveSession, $this>
     */
    public function taughtSessions(): HasMany
    {
        return $this->hasMany(LiveSession::class, 'teacher_id');
    }

    /**
     * Ratings this user has received as a teacher.
     *
     * @return HasMany<Rating, $this>
     */
    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'teacher_id');
    }
}
