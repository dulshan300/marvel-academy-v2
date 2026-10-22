<?php

namespace App\Console\Commands;

use App\Actions\Backups\RestoreDatabaseBackup;
use App\Support\BackupArchives;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('backups:restore {path? : Backup path on the backup disk; defaults to the newest} {--force : Skip the confirmation prompt}')]
#[Description('Replace the database with the dump from a backup archive (destructive)')]
class RestoreBackup extends Command
{
    public function handle(RestoreDatabaseBackup $restore, BackupArchives $archives): int
    {
        $path = $this->argument('path') ?? $archives->all()->first()['path'] ?? null;

        if ($path === null) {
            $this->components->error('No backups found.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Restore [{$path}]? Every change since that backup will be lost.")) {
            return self::FAILURE;
        }

        try {
            $restore->handle($path);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Database restored from [{$path}].");

        return self::SUCCESS;
    }
}
