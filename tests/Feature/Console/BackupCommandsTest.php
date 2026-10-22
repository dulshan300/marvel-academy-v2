<?php

namespace Tests\Feature\Console;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Restores shell out to psql, which is faked; nothing connects to PostgreSQL.
        config(['database.default' => 'pgsql']);
    }

    public function test_mirror_copies_new_and_changed_files_and_prunes_removed_ones(): void
    {
        $source = Storage::fake('s3');
        $backups = Storage::fake('backups');
        $source->put('1/study-plan.pdf', 'v2 contents');
        $source->put('2/slides.pptx', 'slides');
        $backups->put('storage-mirror/1/study-plan.pdf', 'v1');
        $backups->put('storage-mirror/2/slides.pptx', 'slides');
        $backups->put('storage-mirror/3/deleted.pdf', 'old');

        $this->artisan('backups:mirror-storage', ['--prune' => true])
            ->expectsOutputToContain('1 copied, 1 unchanged, 1 pruned')
            ->assertSuccessful();

        $this->assertSame('v2 contents', $backups->get('storage-mirror/1/study-plan.pdf'));
        $backups->assertMissing('storage-mirror/3/deleted.pdf');
    }

    public function test_mirror_keeps_removed_files_without_prune(): void
    {
        Storage::fake('s3');
        Storage::fake('backups')->put('storage-mirror/3/deleted.pdf', 'old');

        $this->artisan('backups:mirror-storage')->assertSuccessful();

        Storage::disk('backups')->assertExists('storage-mirror/3/deleted.pdf');
    }

    public function test_restore_loads_the_dump_from_the_newest_backup(): void
    {
        Process::fake();
        $backups = Storage::fake('backups');
        $backups->put('marvel-academy/2026-10-01-01-30-00.zip', $this->archive('-- old dump'));
        touch($backups->path('marvel-academy/2026-10-01-01-30-00.zip'), now()->subDays(2)->getTimestamp());
        $backups->put('marvel-academy/2026-10-03-01-30-00.zip', $this->archive('-- newest dump'));

        $this->artisan('backups:restore', ['--force' => true])
            ->expectsOutputToContain('2026-10-03-01-30-00.zip')
            ->assertSuccessful();

        Process::assertRanTimes(fn (PendingProcess $process) => str_contains((string) $process->input, 'DROP SCHEMA public CASCADE'), 1);
        Process::assertRan(fn (PendingProcess $process) => $process->input === '-- newest dump');
        $this->assertFalse($this->app->isDownForMaintenance());
    }

    public function test_failed_restore_brings_the_site_back_up(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'permission denied', exitCode: 1)]);
        Storage::fake('backups')->put('marvel-academy/2026-10-03-01-30-00.zip', $this->archive('-- dump'));

        $this->artisan('backups:restore', ['--force' => true])
            ->expectsOutputToContain('permission denied')
            ->assertFailed();

        $this->assertFalse($this->app->isDownForMaintenance());
    }

    public function test_restore_without_backups_fails(): void
    {
        Storage::fake('backups');

        $this->artisan('backups:restore', ['--force' => true])
            ->expectsOutputToContain('No backups found')
            ->assertFailed();
    }

    public function test_restore_rejects_an_archive_without_a_database_dump(): void
    {
        Process::fake();
        Storage::fake('backups')->put('marvel-academy/files-only.zip', $this->archive(null));

        $this->artisan('backups:restore', ['path' => 'marvel-academy/files-only.zip', '--force' => true])
            ->expectsOutputToContain('does not contain a database dump')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public function test_restore_refuses_databases_other_than_postgres(): void
    {
        Process::fake();
        config(['database.default' => 'sqlite']);
        Storage::fake('backups')->put('marvel-academy/2026-10-03-01-30-00.zip', $this->archive('-- dump'));

        $this->artisan('backups:restore', ['--force' => true])
            ->expectsOutputToContain('only supports PostgreSQL')
            ->assertFailed();

        Process::assertNothingRan();
        $this->assertFalse($this->app->isDownForMaintenance());
    }

    /**
     * Build a backup archive shaped like spatie/laravel-backup's output.
     */
    private function archive(?string $dump): string
    {
        $path = tempnam(sys_get_temp_dir(), 'backup');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('storage/app/readme.txt', 'files');

        if ($dump !== null) {
            $zip->addFromString('db-dumps/postgresql-marvel_academy.sql', $dump);
        }

        $zip->close();
        $contents = file_get_contents($path);
        unlink($path);

        return $contents;
    }
}
