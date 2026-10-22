<?php

namespace App\Jobs;

use App\Actions\Backups\RestoreDatabaseBackup;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Restore the database from a backup, requested from the admin panel.
 */
class RestoreBackupJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public string $backupPath, public int $requestedById) {}

    public function handle(RestoreDatabaseBackup $restore): void
    {
        Log::warning('Database restore requested from the admin panel.', [
            'backup' => $this->backupPath,
            'user_id' => $this->requestedById,
        ]);

        $restore->handle($this->backupPath);
    }
}
