<?php

namespace App\Models;

use App\Enums\LiveSessionStatus;
use App\Models\Concerns\HasPublicUlid;
use Database\Factories\LiveSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'course_id', 'topic_id', 'teacher_id', 'title', 'description', 'starts_at',
    'duration_minutes', 'stream_url', 'recording_url', 'status',
])]
class LiveSession extends Model
{
    /** @use HasFactory<LiveSessionFactory> */
    use HasFactory, HasPublicUlid;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'status' => LiveSessionStatus::class,
            'seven_day_reminder_sent_at' => 'datetime',
            'two_day_reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<Carbon, never>
     */
    protected function endsAt(): Attribute
    {
        return Attribute::get(fn (): Carbon => $this->starts_at->copy()->addMinutes($this->duration_minutes));
    }

    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('status', LiveSessionStatus::Scheduled)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return HasMany<SessionAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class);
    }

    /**
     * @return HasMany<Rating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
