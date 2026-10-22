<?php

namespace App\Models;

use Database\Factories\TeacherProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['user_id', 'headline', 'bio', 'qualifications', 'years_experience', 'dbs_checked', 'is_featured'])]
class TeacherProfile extends Model implements HasMedia
{
    /** @use HasFactory<TeacherProfileFactory> */
    use HasFactory, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'dbs_checked' => 'boolean',
            'is_featured' => 'boolean',
            'rating_average' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 256, 256)
            ->format('webp');
    }

    /**
     * Recalculate the cached rating aggregate from the ratings table.
     */
    public function refreshRatingAggregate(): void
    {
        $ratings = Rating::query()->where('teacher_id', $this->user_id);

        $this->forceFill([
            'rating_average' => round((float) $ratings->avg('score'), 2),
            'rating_count' => $ratings->count(),
        ])->save();
    }
}
