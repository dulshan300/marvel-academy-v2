<?php

use App\Models\LiveSession;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Teacher dashboard')] class extends Component
{
    /**
     * @return Collection<int, LiveSession>
     */
    #[Computed]
    public function upcomingSessions(): Collection
    {
        return auth()->user()->taughtSessions()
            ->upcoming()
            ->with('course')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, \App\Models\Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return auth()->user()->taughtCourses()
            ->withCount(['enrolments' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('key_stage')
            ->get();
    }

    /**
     * @return Collection<int, Rating>
     */
    #[Computed]
    public function recentRatings(): Collection
    {
        return auth()->user()->ratingsReceived()
            ->with('liveSession')
            ->latest()
            ->limit(5)
            ->get();
    }
};
?>

@php($profile = auth()->user()->teacherProfile)

<div class="space-y-8">
    <dl class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <dt class="text-sm text-slate-500">Average rating</dt>
            <dd class="mt-1 flex items-center gap-2 font-display text-3xl font-extrabold text-brand-950">
                {{ $profile?->rating_count ? number_format((float) $profile->rating_average, 1) : '—' }}
                @if ($profile?->rating_count) <x-site.stars :rating="(float) $profile->rating_average" /> @endif
            </dd>
            <dd class="mt-1 text-xs text-slate-500">{{ $profile?->rating_count ?? 0 }} ratings</dd>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <dt class="text-sm text-slate-500">Courses</dt>
            <dd class="mt-1 font-display text-3xl font-extrabold text-brand-950">{{ $this->courses->count() }}</dd>
            <dd class="mt-1 text-xs text-slate-500">{{ $this->courses->sum('enrolments_count') }} enrolled students</dd>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <dt class="text-sm text-slate-500">Next session</dt>
            <dd class="mt-1 font-display text-xl font-extrabold text-brand-950">{{ $this->upcomingSessions->first()?->starts_at->format('D j M, H:i') ?? 'None scheduled' }}</dd>
        </div>
    </dl>

    <div class="grid gap-8 xl:grid-cols-3">
        <x-portal.card title="Upcoming sessions" class="xl:col-span-2">
            <x-slot:actions>
                <a href="{{ route('teacher.schedule') }}" wire:navigate class="text-sm font-semibold text-brand-700">Full schedule</a>
            </x-slot:actions>
            @forelse ($this->upcomingSessions as $session)
                <a href="{{ route('teacher.sessions.show', $session) }}" wire:navigate wire:key="session-{{ $session->id }}" class="flex items-center justify-between gap-4 border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0">
                    <div>
                        <p class="font-semibold text-slate-800">{{ $session->title }}</p>
                        <p class="text-sm text-slate-500">{{ $session->course->key_stage->label() }} {{ $session->course->title }}</p>
                    </div>
                    <span class="shrink-0 text-sm text-slate-600">{{ $session->starts_at->format('D j M, H:i') }}</span>
                </a>
            @empty
                <p class="text-sm text-slate-500">No upcoming sessions assigned to you.</p>
            @endforelse
        </x-portal.card>

        <x-portal.card title="Recent feedback">
            @forelse ($this->recentRatings as $rating)
                <div wire:key="rating-{{ $rating->id }}" class="border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0">
                    <x-site.stars :rating="$rating->score" />
                    @if ($rating->comment)
                        <p class="mt-1 text-sm text-slate-700">&ldquo;{{ $rating->comment }}&rdquo;</p>
                    @endif
                    <p class="mt-1 text-xs text-slate-500">{{ $rating->liveSession?->title }} · {{ $rating->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Ratings from students appear here after sessions.</p>
            @endforelse
        </x-portal.card>
    </div>

    <x-portal.card title="My courses">
        <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @forelse ($this->courses as $course)
                <li class="rounded-xl bg-slate-50 p-4" wire:key="course-{{ $course->id }}">
                    <p class="font-semibold text-slate-800">{{ $course->key_stage->label() }} {{ $course->title }}</p>
                    <p class="text-sm text-slate-500">{{ $course->enrolments_count }} students</p>
                </li>
            @empty
                <li class="text-sm text-slate-500">You have not been assigned to a course yet.</li>
            @endforelse
        </ul>
    </x-portal.card>
</div>
