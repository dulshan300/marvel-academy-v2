<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['quiz_id', 'child_id', 'score', 'max_score', 'percentage', 'passed', 'started_at', 'completed_at'])]
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory, HasPublicUlid;

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'parent_notified_at' => 'datetime',
        ];
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * When a timed attempt must be finished by, or null for untimed quizzes.
     */
    public function deadline(): ?Carbon
    {
        $minutes = $this->loadMissing('quiz')->quiz->time_limit_minutes;

        return $minutes ? $this->started_at->copy()->addMinutes($minutes) : null;
    }

    /**
     * Whether the time limit has passed, allowing a short grace period for
     * answers that were in flight when the timer ran out.
     */
    public function hasExpired(int $graceSeconds = 0): bool
    {
        return $this->deadline()?->copy()->addSeconds($graceSeconds)->isPast() ?? false;
    }

    /**
     * Saved answers as question ID => selected option ID.
     *
     * @return array<int, int>
     */
    public function savedAnswers(): array
    {
        return $this->answers()
            ->whereNotNull('question_option_id')
            ->pluck('question_option_id', 'question_id')
            ->map(fn ($optionId) => (int) $optionId)
            ->all();
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<Child, $this>
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * @return HasMany<QuizAttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }
}
