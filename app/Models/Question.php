<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['quiz_id', 'type', 'description', 'prompt', 'explanation', 'marks', 'position'])]
class Question extends Model implements HasMedia
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'text',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
        ];
    }

    public function isPicture(): bool
    {
        return $this->type === QuestionType::Picture;
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->useDisk(config('media-library.private_disk_name'));
    }
}
