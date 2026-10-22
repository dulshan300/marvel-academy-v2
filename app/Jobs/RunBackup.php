<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

/**
 * Run an on-demand backup from the admin panel without blocking the request.
 */
class RunBackup implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public bool $mirrorStorage = true) {}

    public function handle(): void
    {
        Artisan::call('backup:run');

        if ($this->mirrorStorage) {
            Artisan::call('backups:mirror-storage');
        }
    }
}
