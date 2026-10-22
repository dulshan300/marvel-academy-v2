<?php

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'name', 'year_group', 'description', 'position'])]
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'year_group' => 'integer',
        ];
    }

    /**
     * Subjects for the given school year, plus those open to every year.
     */
    #[Scope]
    protected function forYearGroup(Builder $query, int $yearGroup): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereNull('year_group')
            ->orWhere('year_group', $yearGroup));
    }

    public function isForYearGroup(int $yearGroup): bool
    {
        return $this->year_group === null || $this->year_group === $yearGroup;
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<Topic, $this>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('position');
    }
}
