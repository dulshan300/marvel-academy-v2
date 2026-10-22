<?php

namespace App\Actions\Backups;

use App\Support\BackupArchives;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use ZipArchive;

class RestoreDatabaseBackup
{
    public function __construct(private BackupArchives $archives) {}

    /**
     * Replace the database with the dump inside a backup archive.
     *
     * The application is put into maintenance mode for the duration, the
     * public schema is dropped and recreated, and the dump is loaded with
     * psql. This is destructive: everything written since the backup is lost.
     */
    public function handle(string $backupPath): void
    {
        if (config('database.connections.'.config('database.default').'.driver') !== 'pgsql') {
            throw new RuntimeException('Restoring backups only supports PostgreSQL.');
        }

        $disk = $this->archives->disk();

        if (! $disk->exists($backupPath)) {
            throw new RuntimeException("Backup [{$backupPath}] was not found.");
        }

        $workDirectory = storage_path('app/backup-temp/restore-'.now()->format('YmdHis'));
        @mkdir($workDirectory, 0755, true);
        $zipPath = $workDirectory.'/backup.zip';

        file_put_contents($zipPath, $disk->readStream($backupPath));

        $dumpPath = $this->extractDump($zipPath, $workDirectory);

        Artisan::call('down', ['--retry' => 60]);

        try {
            $this->psql('DROP SCHEMA public CASCADE; CREATE SCHEMA public;');
            $this->psql(file_get_contents($dumpPath));

            Log::warning('Database restored from backup.', ['backup' => $backupPath]);
        } finally {
            Artisan::call('up');
            $this->cleanUp($workDirectory);
        }
    }

    private function extractDump(string $zipPath, string $workDirectory): string
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('The backup archive could not be opened.');
        }

        $dumpName = collect(range(0, $zip->numFiles - 1))
            ->map(fn (int $index) => $zip->getNameIndex($index))
            ->first(function (string $name): bool {
                // Archives created on Windows use backslash separators.
                $normalised = str_replace('\\', '/', $name);

                return str_starts_with($normalised, 'db-dumps/') && str_ends_with($normalised, '.sql');
            });

        if ($dumpName === null) {
            $zip->close();

            throw new RuntimeException('The backup archive does not contain a database dump.');
        }

        $zip->extractTo($workDirectory, $dumpName);
        $zip->close();

        return $workDirectory.'/'.$dumpName;
    }

    private function psql(string $sql): void
    {
        $connection = config('database.connections.'.config('database.default'));
        $binary = rtrim((string) ($connection['dump']['dump_binary_path'] ?? ''), '/\\');
        $binary = $binary === '' ? 'psql' : $binary.DIRECTORY_SEPARATOR.'psql';

        if (PHP_OS_FAMILY === 'Windows' && is_file($binary.'.bat')) {
            $binary .= '.bat';
        }

        $result = Process::timeout(600)
            ->env(['PGPASSWORD' => (string) $connection['password']])
            ->input($sql)
            ->run([
                $binary,
                '-U', $connection['username'],
                '-d', $connection['database'],
                '-h', $connection['host'],
                '-p', (string) $connection['port'],
                '-v', 'ON_ERROR_STOP=1',
                '-q',
            ]);

        if ($result->failed()) {
            throw new RuntimeException('Restore failed: '.trim($result->errorOutput()));
        }
    }

    private function cleanUp(string $directory): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($directory);
    }
}
