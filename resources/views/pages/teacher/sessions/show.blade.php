<?php

use App\Enums\EnrolmentStatus;
use App\Enums\LiveSessionStatus;
use App\Models\Child;
use App\Models\LiveSession;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts::portal')] class extends Component
{
    #[Locked]
    public LiveSession $session;

    public string $recording_url = '';

    public function mount(LiveSession $session): void
    {
        $this->authorize('teach', $session);

        $this->session = $session->load('course', 'topic');
        $this->recording_url = (string) $session->recording_url;
    }

    public function render()
    {
        return $this->view()->title($this->session->title);
    }

    /**
     * Every student enrolled on the course, with whether they joined.
     *
     * @return Collection<int, Child>
     */
    #[Computed]
    public function roster(): Collection
    {
        return Child::query()
            ->whereHas('enrolments', fn ($query) => $query->where('course_id', $this->session->course_id)->where('status', EnrolmentStatus::Active))
            ->with(['attendances' => fn ($query) => $query->where('live_session_id', $this->session->id)])
            ->orderBy('first_name')
            ->get();
    }

    public function saveRecording(): void
    {
        $this->authorize('teach', $this->session);

        $validated = $this->validate(['recording_url' => ['nullable', 'url:https', 'max:2048']]);

        $this->session->update(['recording_url' => $validated['recording_url'] ?: null]);

        session()->flash('status', 'Recording link saved.');
    }

    public function markCompleted(): void
    {
        $this->authorize('teach', $this->session);

        $this->session->update(['status' => LiveSessionStatus::Completed]);
    }
};
?>

<div class="grid gap-8 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <x-portal.card :title="$session->title">
            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">Course</dt><dd class="font-semibold text-slate-800">{{ $session->course->key_stage->label() }} {{ $session->course->title }}</dd></div>
                <div><dt class="text-slate-500">Starts</dt><dd class="font-semibold text-slate-800">{{ $session->starts_at->format('D j M Y, H:i') }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd><x-portal.badge tone="brand">{{ $session->status->label() }}</x-portal.badge></dd></div>
            </dl>
            @if ($session->stream_url)
                <a href="{{ $session->stream_url }}" target="_blank" rel="noopener" class="btn-primary mt-6">Open stream link</a>
            @endif
            @if ($session->status !== \App\Enums\LiveSessionStatus::Completed && $session->starts_at->isPast())
                <button type="button" wire:click="markCompleted" class="btn-ghost mt-6 ml-2 text-slate-700">Mark as completed</button>
            @endif
        </x-portal.card>

        <x-portal.card title="Recording">
            <form wire:submit="saveRecording" class="flex gap-3">
                <input type="url" wire:model="recording_url" placeholder="https://www.youtube.com/watch?v=…" class="form-input" aria-label="Recording link">
                <button type="submit" class="btn-secondary shrink-0">Save</button>
            </form>
            @error('recording_url') <p class="form-error">{{ $message }}</p> @enderror
        </x-portal.card>
    </div>

    <x-portal.card :title="'Students ('.$this->roster->filter(fn ($child) => $child->attendances->isNotEmpty())->count().'/'.$this->roster->count().' joined)'">
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($this->roster as $child)
                <li class="flex items-center justify-between py-2" wire:key="roster-{{ $child->id }}">
                    <span class="font-medium text-slate-800">{{ $child->full_name }}</span>
                    @if ($attendance = $child->attendances->first())
                        <x-portal.badge tone="green">Joined {{ $attendance->joined_at->format('H:i') }}</x-portal.badge>
                    @else
                        <x-portal.badge tone="slate">Not joined</x-portal.badge>
                    @endif
                </li>
            @empty
                <li class="py-2 text-slate-500">No students enrolled.</li>
            @endforelse
        </ul>
    </x-portal.card>
</div>
