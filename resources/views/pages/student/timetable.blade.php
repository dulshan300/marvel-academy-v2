<?php

use App\Models\LiveSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Timetable')] class extends Component
{
    #[Url]
    public string $month = '';

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
     * @return Collection<int, LiveSession>
     */
    #[Computed]
    public function sessions(): Collection
    {
        $child = auth()->user()->studentProfile;
        $courseIds = $child?->activeCourses()->pluck('courses.id') ?? collect();

        return LiveSession::query()
            ->whereIn('course_id', $courseIds)
            ->when($child, fn ($query) => $query->forYearGroup($child->year_group))
            ->whereBetween('starts_at', [$this->monthDate->copy()->startOfWeek(), $this->monthDate->copy()->endOfMonth()->endOfWeek()])
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
    <div class="flex items-center justify-between">
        <h2 class="font-display text-2xl font-extrabold text-brand-950">{{ $this->monthDate->format('F Y') }}</h2>
        <div class="flex gap-2">
            <button type="button" wire:click="previousMonth" class="btn-ghost py-2 text-slate-700">&larr; <span class="sr-only">Previous month</span></button>
            <button type="button" wire:click="nextMonth" class="btn-ghost py-2 text-slate-700"><span class="sr-only">Next month</span> &rarr;</button>
        </div>
    </div>

    <x-portal.calendar :month="$this->monthDate" :sessions="$this->sessions" :link="fn ($session) => route('student.sessions.show', $session)" />
</div>
