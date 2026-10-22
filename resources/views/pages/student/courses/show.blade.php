<?php

use App\Models\Course;
use App\Models\LiveSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts::portal')] class extends Component
{
    #[Locked]
    public Course $course;

    public function mount(Course $course): void
    {
        $this->authorize('study', $course);

        $this->course = $course;
    }

    public function render()
    {
        return $this->view()->title($this->course->key_stage->label().' '.$this->course->title);
    }

    /**
     * @return Collection<int, \App\Models\Subject>
     */
    #[Computed]
    public function subjects(): Collection
    {
        return $this->course->subjects()
            ->with([
                'topics.materials.media',
                'topics.quizzes' => fn ($query) => $query->published()->withCount('questions'),
            ])
            ->get();
    }

    /**
     * Best completed score per quiz for this student.
     *
     * @return SupportCollection<int, int>
     */
    #[Computed]
    public function bestScores(): SupportCollection
    {
        return auth()->user()->studentProfile->quizAttempts()
            ->whereNotNull('completed_at')
            ->selectRaw('quiz_id, MAX(percentage) as best')
            ->groupBy('quiz_id')
            ->pluck('best', 'quiz_id')
            ->map(fn ($best) => (int) $best);
    }

    /**
     * @return Collection<int, LiveSession>
     */
    #[Computed]
    public function recordings(): Collection
    {
        return $this->course->liveSessions()
            ->whereNotNull('recording_url')
            ->where('starts_at', '<', now())
            ->latest('starts_at')
            ->limit(12)
            ->get();
    }
};
?>

<div class="grid gap-8 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        @foreach ($this->subjects as $subject)
            <x-portal.card :title="$subject->name" wire:key="subject-{{ $subject->id }}">
                <div class="divide-y divide-slate-100">
                    @foreach ($subject->topics as $topic)
                        <details class="group py-4 first:pt-0 last:pb-0" wire:key="topic-{{ $topic->id }}">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <span class="font-semibold text-slate-800">{{ $topic->title }}</span>
                                <span class="flex shrink-0 items-center gap-2">
                                    @foreach ($topic->quizzes as $quiz)
                                        @if (isset($this->bestScores[$quiz->id]))
                                            <x-portal.badge :tone="$this->bestScores[$quiz->id] >= $quiz->pass_mark ? 'green' : 'amber'">{{ $this->bestScores[$quiz->id] }}%</x-portal.badge>
                                        @endif
                                    @endforeach
                                    <svg class="size-5 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </summary>

                            <div class="mt-4 space-y-4">
                                @if ($topic->description)
                                    <p class="text-sm text-slate-600">{{ $topic->description }}</p>
                                @endif

                                @if ($topic->materials->isNotEmpty())
                                    <ul class="space-y-2">
                                        @foreach ($topic->materials as $material)
                                            <li class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm">
                                                <span>
                                                    <span class="font-medium text-slate-800">{{ $material->title }}</span>
                                                    <span class="text-slate-500">· {{ $material->type->label() }}</span>
                                                </span>
                                                <a href="{{ route('student.materials.download', $material) }}" target="_blank" rel="noopener" class="shrink-0 font-semibold text-brand-700 hover:text-brand-900">
                                                    {{ $material->getFirstMedia('file') ? 'Download' : 'Open' }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @foreach ($topic->quizzes as $quiz)
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-100 bg-brand-50 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-semibold text-brand-950">{{ $quiz->title }}</p>
                                            <p class="text-xs text-brand-800">{{ $quiz->questions_count }} questions · pass mark {{ $quiz->pass_mark }}%@if ($quiz->time_limit_minutes) · {{ $quiz->time_limit_minutes }} min @endif</p>
                                        </div>
                                        <a href="{{ route('student.quizzes.take', $quiz) }}" wire:navigate class="btn-secondary shrink-0 py-2">
                                            {{ isset($this->bestScores[$quiz->id]) ? 'Retake' : 'Start quiz' }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            </x-portal.card>
        @endforeach
    </div>

    <x-portal.card title="Recordings">
        @forelse ($this->recordings as $session)
            <a href="{{ route('student.sessions.show', $session) }}" wire:navigate wire:key="recording-{{ $session->id }}" class="block border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0">
                <p class="text-sm font-semibold text-slate-800">{{ $session->title }}</p>
                <p class="text-xs text-slate-500">{{ $session->starts_at->format('j M Y') }}</p>
            </a>
        @empty
            <p class="text-sm text-slate-500">Recordings appear here after each live session.</p>
        @endforelse
    </x-portal.card>
</div>
