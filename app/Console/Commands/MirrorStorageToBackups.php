<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('backups:mirror-storage {--prune : Remove mirrored files that no longer exist in the source}')]
#[Description('Copy course materials from object storage into the backup bucket')]
class MirrorStorageToBackups extends Command
{
    /**
     * Folder on the backup disk that holds the mirror.
     */
    public const MIRROR_PREFIX = 'storage-mirror';

    public function handle(): int
    {
        $source = Storage::disk(config('media-library.private_disk_name'));
        $destination = Storage::disk(config('backup.backup.destination.disks')[0]);

        $copied = 0;
        $skipped = 0;
        $sourceFiles = $source->allFiles();

        foreach ($sourceFiles as $path) {
            $target = self::MIRROR_PREFIX.'/'.$path;

            if ($destination->exists($target) && $destination->size($target) === $source->size($path)) {
                $skipped++;

                continue;
            }

            $destination->writeStream($target, $source->readStream($path));
            $copied++;
        }

        $pruned = 0;

        if ($this->option('prune')) {
            $expected = array_flip(array_map(fn (string $path) => self::MIRROR_PREFIX.'/'.$path, $sourceFiles));

            foreach ($destination->allFiles(self::MIRROR_PREFIX) as $mirrored) {
                if (! isset($expected[$mirrored])) {
                    $destination->delete($mirrored);
                    $pruned++;
                }
            }
        }

        $this->components->info("Mirrored storage: {$copied} copied, {$skipped} unchanged, {$pruned} pruned.");

        return self::SUCCESS;
    }
}
