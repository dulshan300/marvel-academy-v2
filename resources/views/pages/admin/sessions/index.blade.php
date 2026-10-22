<?php

use App\Models\Course;
use App\Models\LiveSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Live sessions')] class extends Component
{
    #[Url]
    public string $month = '';

    #[Url(except: '')]
    public string $course = '';

    #[Computed]
    public function monthDate(): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    /**
     * @return Collection<int, Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return Course::orderBy('key_stage')->orderBy('title')->get();
    }

    /**
     * @return Collection<int, LiveSession>
     */
    #[Computed]
    public function sessions(): Collection
    {
        return LiveSession::query()
            ->when($this->course !== '', fn ($query) => $query->where('course_id', (int) $this->course))
            ->whereBetween('starts_at', [$this->monthDate->copy()->startOfWeek(), $this->monthDate->copy()->endOfMonth()->endOfWeek()])
            ->with('course', 'teacher')
            ->withCount('attendances')
            ->orderBy('starts_at')
            ->get();
    }

    public function previousMonth(): void
    {
        $this->month = $this->monthDate->copy()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthDate->copy()->addMonth()->format('Y-m');
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
        <div class="flex items-center gap-3">
            <button type="button" wire:click="previousMonth" class="btn-ghost py-2 text-slate-700">&larr;<span class="sr-only">Previous month</span></button>
            <h2 class="font-display text-2xl font-extrabold text-brand-950">{{ $this->monthDate->format('F Y') }}</h2>
            <button type="button" wire:click="nextMonth" class="btn-ghost py-2 text-slate-700"><span class="sr-only">Next month</span>&rarr;</button>
        </div>
        <div class="flex gap-3">
            <select wire:model.live="course" class="form-input" aria-label="Filter by course">
                <option value="">All courses</option>
                @foreach ($this->courses as $option)
                    <option value="{{ $option->id }}">{{ $option->key_stage->label() }} {{ $option->title }}</option>
                @endforeach
            </select>
            <a href="{{ route('admin.sessions.create') }}" wire:navigate class="btn-primary shrink-0">Schedule session</a>
        </div>
    </div>

    <x-portal.calendar :month="$this->monthDate" :sessions="$this->sessions" :link="fn ($session) => route('admin.sessions.edit', $session)" />

    <x-portal.card title="Sessions this month">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-slate-500 uppercase"><tr><th class="pb-2">When</th><th class="pb-2">Session</th><th class="pb-2">Tutor</th><th class="pb-2">Status</th><th class="pb-2">Attended</th><th class="pb-2">Reminders</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->sessions as $session)
                        <tr wire:key="row-{{ $session->id }}">
                            <td class="py-3 whitespace-nowrap text-slate-600">{{ $session->starts_at->format('D j M, H:i') }}</td>
                            <td class="py-3"><a href="{{ route('admin.sessions.edit', $session) }}" wire:navigate class="font-medium text-brand-700 hover:text-brand-900">{{ $session->title }}</a><p class="text-xs text-slate-500">{{ $session->course->key_stage->label() }} {{ $session->course->title }}</p></td>
                            <td class="py-3 text-slate-600">{{ $session->teacher?->name ?? '—' }}</td>
                            <td class="py-3"><x-portal.badge :tone="match ($session->status) { \App\Enums\LiveSessionStatus::Cancelled => 'red', \App\Enums\LiveSessionStatus::Completed => 'slate', default => 'green' }">{{ $session->status->label() }}</x-portal.badge></td>
                            <td class="py-3 text-slate-600">{{ $session->attendances_count }}</td>
                            <td class="py-3 text-xs text-slate-500">
                                7d {{ $session->seven_day_reminder_sent_at ? '✓' : '–' }} · 2d {{ $session->two_day_reminder_sent_at ? '✓' : '–' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-3 text-slate-500">No sessions this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-portal.card>
</div>
