<?php

use App\Enums\Permission;
use App\Models\EmailLog;
use App\Support\EmailPreviews;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::portal')] #[Title('Emails')] class extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public string $template = 'session-preparation';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function logs(): LengthAwarePaginator
    {
        return EmailLog::query()
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('recipients', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%")))
            ->latest('sent_at')
            ->paginate(20);
    }

    #[Computed]
    public function sentToday(): int
    {
        return EmailLog::where('sent_at', '>=', today())->count();
    }

    #[Computed]
    public function queueSize(): ?int
    {
        try {
            return Queue::size();
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @return Collection<int, object>
     */
    #[Computed]
    public function failedJobs(): Collection
    {
        return collect(app('queue.failer')->all())
            ->map(function (object $job): object {
                $payload = json_decode($job->payload, true);
                $job->name = $payload['displayName'] ?? 'Unknown job';
                $job->error = str($job->exception)->before("\n")->limit(200)->toString();

                return $job;
            });
    }

    public function retry(string $uuid): void
    {
        $this->authorize(Permission::ManageEmails->value);

        Artisan::call('queue:retry', ['id' => [$uuid]]);
        unset($this->failedJobs, $this->queueSize);
        session()->flash('status', 'The job was pushed back onto the queue.');
    }

    public function retryAll(): void
    {
        $this->authorize(Permission::ManageEmails->value);

        Artisan::call('queue:retry', ['id' => ['all']]);
        unset($this->failedJobs, $this->queueSize);
        session()->flash('status', 'All failed jobs were pushed back onto the queue.');
    }

    public function forget(string $uuid): void
    {
        $this->authorize(Permission::ManageEmails->value);

        app('queue.failer')->forget($uuid);
        unset($this->failedJobs);
        session()->flash('status', 'The failed job was discarded.');
    }
};
?>

<div class="space-y-8">
    <div class="grid gap-4 sm:grid-cols-3">
        <x-portal.card>
            <p class="text-sm text-slate-500">Sent today</p>
            <p class="font-display text-3xl font-bold text-slate-800">{{ $this->sentToday }}</p>
        </x-portal.card>
        <x-portal.card>
            <p class="text-sm text-slate-500">Waiting in the queue</p>
            <p class="font-display text-3xl font-bold text-slate-800">{{ $this->queueSize ?? '—' }}</p>
            @if ($this->queueSize === null)
                <p class="text-xs text-amber-700">The queue connection could not be reached.</p>
            @endif
        </x-portal.card>
        <x-portal.card>
            <p class="text-sm text-slate-500">Failed jobs</p>
            <p class="font-display text-3xl font-bold {{ $this->failedJobs->isEmpty() ? 'text-emerald-700' : 'text-red-700' }}">{{ $this->failedJobs->count() }}</p>
        </x-portal.card>
    </div>

    @if ($this->failedJobs->isNotEmpty())
        <x-portal.card title="Failed jobs">
            <x-slot:actions>
                <button type="button" wire:click="retryAll" class="btn-ghost py-2 text-brand-700">Retry all</button>
            </x-slot:actions>
            <ul class="divide-y divide-slate-100">
                @foreach ($this->failedJobs as $job)
                    <li class="flex flex-wrap items-start justify-between gap-3 py-3" wire:key="failed-{{ $job->id }}">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-800">{{ class_basename($job->name) }}</p>
                            <p class="text-xs text-slate-500">Failed {{ \Illuminate\Support\Carbon::parse($job->failed_at)->diffForHumans() }} on {{ $job->queue }}</p>
                            <p class="mt-1 text-sm break-words text-red-700">{{ $job->error }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" wire:click="retry('{{ $job->id }}')" class="btn-ghost py-2 text-brand-700">Retry</button>
                            <button type="button" wire:click="forget('{{ $job->id }}')" wire:confirm="Discard this failed job?" class="btn-ghost py-2 text-red-700">Discard</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-portal.card>
    @endif

    <x-portal.card title="Template preview">
        <x-slot:actions>
            <select wire:model.live="template" class="form-input py-2" aria-label="Email template">
                @foreach (EmailPreviews::templates() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </x-slot:actions>
        <p class="mb-3 text-sm text-slate-500">Rendered with the most recent real records. Nothing is sent.</p>
        <iframe src="{{ route('admin.emails.preview', $template) }}" title="Email preview" class="h-[36rem] w-full rounded-xl border border-slate-200 bg-white" wire:key="preview-{{ $template }}"></iframe>
    </x-portal.card>

    <x-portal.card title="Sent emails">
        <x-slot:actions>
            <input type="search" wire:model.live.debounce.400ms="search" class="form-input py-2" placeholder="Search recipient or subject" aria-label="Search sent emails">
        </x-slot:actions>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-slate-500 uppercase"><tr><th class="pb-2">Sent</th><th class="pb-2">To</th><th class="pb-2">Subject</th><th class="pb-2">Type</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->logs as $log)
                        <tr wire:key="log-{{ $log->id }}">
                            <td class="py-3 whitespace-nowrap text-slate-500">{{ $log->sent_at->format('j M Y, H:i') }}</td>
                            <td class="py-3 text-slate-700">{{ $log->recipients }}</td>
                            <td class="py-3 font-medium text-slate-800">{{ $log->subject }}</td>
                            <td class="py-3"><x-portal.badge tone="slate">{{ $log->typeLabel() }}</x-portal.badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-3 text-slate-500">No emails have been sent yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $this->logs->links() }}</div>
    </x-portal.card>
</div>
