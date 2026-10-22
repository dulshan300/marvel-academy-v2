<?php

use App\Enums\LiveSessionStatus;
use App\Models\Child;
use App\Models\LiveSession;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('My learning')] class extends Component
{
    #[Computed]
    public function child(): Child
    {
        $child = auth()->user()->studentProfile;

        abort_if($child === null, 403, 'This login is not linked to a student yet.');

        return $child->loadMissing('parent.subscriptions', 'plan');
    }

    /**
     * @return Collection<int, \App\Models\Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return $this->child->activeCourses()->with('media')->orderBy('title')->get();
    }

    /**
     * @return Collection<int, LiveSession>
     */
    #[Computed]
    public function upcomingSessions(): Collection
    {
        return LiveSession::query()
            ->whereIn('course_id', $this->courses->modelKeys())
            ->where('starts_at', '>=', now()->subHours(3))
            ->where('status', '!=', LiveSessionStatus::Cancelled)
            ->with('course', 'teacher')
            ->orderBy('starts_at')
            ->limit(5)
            ->get()
            ->reject(fn (LiveSession $session) => $session->ends_at->isPast())
            ->values();
    }

    /**
     * @return Collection<int, QuizAttempt>
     */
    #[Computed]
    public function recentResults(): Collection
    {
        return $this->child->quizAttempts()
            ->whereNotNull('completed_at')
            ->with('quiz')
            ->latest('completed_at')
            ->limit(5)
            ->get();
    }
};
?>

<div class="space-y-8">
    <div>
        <h2 class="text-2xl font-extrabold text-brand-950">Hi {{ $this->child->first_name }} 👋</h2>
        <p class="mt-1 text-slate-600">Year {{ $this->child->year_group }} · {{ $this->child->key_stage->label() }}</p>
    </div>

    @unless ($this->child->hasActiveSubscription())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900" role="alert">
            <p class="font-semibold">Your courses are locked</p>
            <p class="mt-1 text-sm">Ask your parent to choose or renew your Marvel Academy plan to join lessons and take quizzes.</p>
        </div>
    @endunless

    <div class="grid gap-8 xl:grid-cols-3">
        <div class="space-y-8 xl:col-span-2">
            <x-portal.card title="Upcoming live sessions">
                <x-slot:actions>
                    <a href="{{ route('student.timetable') }}" wire:navigate class="text-sm font-semibold text-brand-700">Full timetable</a>
                </x-slot:actions>

                @forelse ($this->upcomingSessions as $session)
                    <div wire:key="session-{{ $session->id }}" class="flex flex-col justify-between gap-3 border-b border-slate-100 py-4 first:pt-0 last:border-0 last:pb-0 sm:flex-row sm:items-center">
                        <div>
                            <p class="font-semibold text-slate-800">{{ $session->title }}</p>
                            <p class="text-sm text-slate-500">{{ $session->starts_at->format('l j F, g:ia') }} · {{ $session->duration_minutes }} min @if ($session->teacher) · {{ $session->teacher->name }} @endif</p>
                        </div>
                        @if ($session->isJoinable() && $this->child->hasActiveSubscription())
                            <a href="{{ route('student.sessions.show', $session) }}" class="btn-primary shrink-0 py-2">
                                <span class="size-2 animate-pulse rounded-full bg-red-600"></span> Join live
                            </a>
                        @else
                            <a href="{{ route('student.sessions.show', $session) }}" wire:navigate class="btn-ghost shrink-0 py-2 text-brand-700">Details</a>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No sessions scheduled yet. Check back soon.</p>
                @endforelse
            </x-portal.card>

            <div>
                <h2 class="mb-4 font-display text-lg font-bold text-brand-950">My courses</h2>
                <div class="grid gap-6 sm:grid-cols-2">
                    @forelse ($this->courses as $course)
                        <a href="{{ route('student.courses.show', $course) }}" wire:navigate wire:key="course-{{ $course->id }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md">
                            <x-portal.badge tone="brand">{{ $course->key_stage->label() }}</x-portal.badge>
                            <h3 class="mt-3 text-lg font-bold text-brand-950 group-hover:text-brand-700">{{ $course->title }}</h3>
                            <p class="mt-1 text-sm text-slate-500">Materials, recordings and quizzes</p>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">You are not enrolled on any courses yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <x-portal.card title="Recent quiz results">
            @forelse ($this->recentResults as $attempt)
                <a href="{{ route('student.quizzes.result', [$attempt->quiz, $attempt]) }}" wire:navigate wire:key="attempt-{{ $attempt->id }}" class="flex items-center justify-between gap-3 border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $attempt->quiz->title }}</p>
                        <p class="text-xs text-slate-500">{{ $attempt->completed_at->diffForHumans() }}</p>
                    </div>
                    <x-portal.badge :tone="$attempt->passed ? 'green' : 'amber'">{{ $attempt->percentage }}%</x-portal.badge>
                </a>
            @empty
                <p class="text-sm text-slate-500">Take a topic quiz to see your scores here.</p>
            @endforelse
        </x-portal.card>
    </div>
</div>
