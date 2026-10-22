<?php

namespace App\Models;

use App\Enums\EnrolmentStatus;
use App\Enums\KeyStage;
use App\Models\Concerns\HasPublicUlid;
use Database\Factories\ChildFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('children')]
#[Fillable(['parent_id', 'student_id', 'first_name', 'last_name', 'date_of_birth', 'key_stage', 'year_group', 'school_name', 'notes'])]
class Child extends Model
{
    /** @use HasFactory<ChildFactory> */
    use HasFactory, HasPublicUlid;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'key_stage' => KeyStage::class,
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * The login account the child uses for the student portal.
     *
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return HasMany<Enrolment, $this>
     */
    public function enrolments(): HasMany
    {
        return $this->hasMany(Enrolment::class);
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrolments')
            ->withPivot(['status', 'enrolled_at'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function activeCourses(): BelongsToMany
    {
        return $this->courses()->wherePivot('status', EnrolmentStatus::Active->value);
    }

    /**
     * @return HasMany<QuizAttempt, $this>
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * @return HasMany<SessionAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class);
    }
}
