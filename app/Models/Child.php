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
use Laravel\Cashier\Subscription;

#[Table('children')]
#[Fillable(['parent_id', 'student_id', 'plan_id', 'first_name', 'last_name', 'date_of_birth', 'key_stage', 'year_group', 'school_name', 'notes'])]
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
     * Key stage a UK school year group belongs to.
     */
    public static function keyStageForYear(int $yearGroup): KeyStage
    {
        return match (true) {
            $yearGroup <= 6 => KeyStage::Ks2,
            $yearGroup <= 9 => KeyStage::Ks3,
            $yearGroup <= 11 => KeyStage::Gcse,
            default => KeyStage::ALevel,
        };
    }

    /**
     * Name of this child's subscription on the parent's billing account.
     */
    public function subscriptionType(): string
    {
        return 'child-'.$this->ulid;
    }

    public function subscription(): ?Subscription
    {
        return $this->parent->subscription($this->subscriptionType());
    }

    /**
     * Whether the child's plan is active, trialling or in its cancellation grace period.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->parent->subscribed($this->subscriptionType());
    }

    /**
     * Number of further courses the child's plan allows.
     */
    public function remainingCourseAllowance(): int
    {
        if ($this->plan === null) {
            return 0;
        }

        return max(0, $this->plan->subjects_included - $this->activeCourses()->count());
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
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
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
