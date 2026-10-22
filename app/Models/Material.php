<?php

namespace App\Models;

use App\Enums\MaterialType;
use App\Models\Concerns\HasPublicUlid;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['topic_id', 'title', 'description', 'type', 'external_url', 'is_preparatory', 'position'])]
class Material extends Model implements HasMedia
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory, HasPublicUlid, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'type' => MaterialType::class,
            'is_preparatory' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Uploaded files live on the private object-storage disk and are only
     * ever served through short-lived signed URLs.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile()
            ->useDisk(config('media-library.private_disk_name'));
    }
}
