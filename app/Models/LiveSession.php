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

    /**
     * Whether students can join now: from 15 minutes before the start until it ends.
     */
    public function isJoinable(): bool
    {
        return $this->status !== LiveSessionStatus::Cancelled
            && now()->between($this->starts_at->copy()->subMinutes(15), $this->ends_at);
    }

    public function hasEnded(): bool
    {
        return $this->status === LiveSessionStatus::Completed || $this->ends_at->isPast();
    }

    /**
     * Privacy-enhanced embed URL for YouTube links, or null for other providers
     * (such as Microsoft Teams), which open in a new tab instead.
     */
    public static function embedUrlFor(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $pattern = '~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|live/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~';

        if (preg_match($pattern, $url, $matches) !== 1) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$matches[1];
    }

    /**
     * Sessions for a school year: those without a topic, or whose topic's subject is for that year.
     */
    #[Scope]
    protected function forYearGroup(Builder $query, int $yearGroup): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereNull('topic_id')
            ->orWhereHas('topic.subject', fn (Builder $query) => $query->forYearGroup($yearGroup)));
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
