<?php

use App\Enums\Permission;
use App\Jobs\RestoreBackupJob;
use App\Jobs\RunBackup;
use App\Support\BackupArchives;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Backups')] class extends Component
{
    public ?string $restorePath = null;

    public string $confirmName = '';

    public string $password = '';

    /**
     * @return Collection<int, array{path: string, name: string, size: int, created_at: \Illuminate\Support\Carbon}>
     */
    #[Computed]
    public function backups(): Collection
    {
        try {
            return app(BackupArchives::class)->all();
        } catch (\Throwable $exception) {
            report($exception);

            return collect();
        }
    }

    /**
     * Whether the newest backup is recent enough for the nightly schedule.
     */
    #[Computed]
    public function isHealthy(): bool
    {
        $newest = $this->backups->first();

        return $newest !== null && $newest['created_at']->gt(now()->subHours(26));
    }

    public function runBackup(): void
    {
        $this->authorize(Permission::ManageBackups->value);

        RunBackup::dispatch();
        session()->flash('status', 'A backup has been queued. It appears here once it finishes, usually within a few minutes.');
    }

    public function delete(string $path, BackupArchives $archives): void
    {
        $this->authorize(Permission::ManageBackups->value);

        abort_unless($archives->exists($path), 404);

        $archives->disk()->delete($path);
        unset($this->backups);
        session()->flash('status', 'Backup '.basename($path).' deleted.');
    }

    public function startRestore(string $path): void
    {
        $this->authorize(Permission::ManageBackups->value);

        $this->restorePath = $path;
        $this->reset('confirmName', 'password');
        $this->resetErrorBag();
    }

    public function cancelRestore(): void
    {
        $this->reset('restorePath', 'confirmName', 'password');
    }

    public function restore(BackupArchives $archives): void
    {
        $this->authorize(Permission::ManageBackups->value);

        abort_unless($this->restorePath !== null && $archives->exists($this->restorePath), 404);

        $this->validate([
            'confirmName' => ['required', Rule::in([basename($this->restorePath)])],
            'password' => ['required', 'current_password'],
        ], [
            'confirmName.in' => 'Type the backup file name exactly as shown.',
        ]);

        RestoreBackupJob::dispatch($this->restorePath, auth()->id());

        session()->flash('status', 'The restore has been queued. The site goes into maintenance mode while it runs, so you may be signed out.');
        $this->cancelRestore();
    }
};
?>

<div class="space-y-8">
    <x-portal.card title="Backups">
        <x-slot:actions>
            <button type="button" wire:click="runBackup" class="btn-primary py-2">Back up now</button>
        </x-slot:actions>

        <div @class([
            'mb-6 rounded-xl p-4 text-sm',
            'bg-emerald-50 text-emerald-800' => $this->isHealthy,
            'bg-amber-50 text-amber-800' => ! $this->isHealthy,
        ])>
            @if ($this->backups->isEmpty())
                No backups found. Backups run nightly at 01:30; use <strong>Back up now</strong> to create one.
            @elseif ($this->isHealthy)
                Last backup {{ $this->backups->first()['created_at']->diffForHumans() }}. Backups run nightly at 01:30 and course files are mirrored at 02:30.
            @else
                The newest backup is {{ $this->backups->first()['created_at']->diffForHumans() }}. Check that the scheduler and queue worker are running.
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-slate-500 uppercase"><tr><th class="pb-2">Backup</th><th class="pb-2">Created</th><th class="pb-2">Size</th><th class="pb-2"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->backups as $backup)
                        <tr wire:key="backup-{{ $backup['name'] }}">
                            <td class="py-3 font-mono text-slate-800">{{ $backup['name'] }}</td>
                            <td class="py-3 text-slate-600">{{ $backup['created_at']->format('j M Y, H:i') }}</td>
                            <td class="py-3 text-slate-600">{{ Number::fileSize($backup['size'], precision: 1) }}</td>
                            <td class="py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.backups.download', ['path' => $backup['path']]) }}" class="btn-ghost py-2 text-brand-700">Download</a>
                                    <button type="button" wire:click="startRestore('{{ $backup['path'] }}')" class="btn-ghost py-2 text-amber-700">Restore</button>
                                    <button type="button" wire:click="delete('{{ $backup['path'] }}')" wire:confirm="Delete {{ $backup['name'] }}? This cannot be undone." class="btn-ghost py-2 text-red-700">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-3 text-slate-500">No backups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-portal.card>

    @if ($restorePath)
        <x-portal.card title="Restore the database" class="border-amber-300">
            <form wire:submit="restore" class="max-w-xl space-y-4" novalidate>
                <p class="text-sm text-slate-700">
                    This replaces the whole database with the copy in <strong class="font-mono">{{ basename($restorePath) }}</strong>.
                    Everything recorded since then (new families, payments, quiz results) is lost. Uploaded files are not changed.
                </p>
                <div>
                    <label for="confirmName" class="form-label">Type the backup file name to confirm</label>
                    <input id="confirmName" type="text" wire:model="confirmName" class="form-input font-mono" autocomplete="off">
                    @error('confirmName') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="form-label">Your password</label>
                    <input id="password" type="password" wire:model="password" class="form-input" autocomplete="current-password">
                    @error('password') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary bg-red-700 hover:bg-red-800">Restore this backup</button>
                    <button type="button" wire:click="cancelRestore" class="btn-ghost">Cancel</button>
                </div>
            </form>
        </x-portal.card>
    @endif
</div>
