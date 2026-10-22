<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * The spatie/laravel-backup archives stored on the backup disk.
 */
class BackupArchives
{
    public function disk(): Filesystem
    {
        return Storage::disk(config('backup.backup.destination.disks')[0]);
    }

    /**
     * Archives newest first.
     *
     * @return Collection<int, array{path: string, name: string, size: int, created_at: Carbon}>
     */
    public function all(): Collection
    {
        $disk = $this->disk();

        return collect($disk->files($this->prefix()))
            ->filter(fn (string $path) => str_ends_with($path, '.zip'))
            ->map(fn (string $path) => [
                'path' => $path,
                'name' => basename($path),
                'size' => $disk->size($path),
                'created_at' => now()->setTimestamp($disk->lastModified($path)),
            ])
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Whether a path names a backup archive, guarding against traversal.
     */
    public function isArchivePath(string $path): bool
    {
        return str_starts_with($path, $this->prefix().'/')
            && str_ends_with($path, '.zip')
            && ! str_contains($path, '..')
            && substr_count($path, '/') === 1;
    }

    public function exists(string $path): bool
    {
        return $this->isArchivePath($path) && $this->disk()->exists($path);
    }

    private function prefix(): string
    {
        return (string) config('backup.backup.name');
    }
}
