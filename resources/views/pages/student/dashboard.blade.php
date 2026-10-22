<?php

use App\Enums\LiveSessionStatus;
use App\Models\Child;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\Quiz;
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
     * @return Collection<int, Course>
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
            ->forYearGroup($this->child->year_group)
            ->where('starts_at', '>=', now()->subHours(4))
            ->where('status', '!=', LiveSessionStatus::Cancelled)
            ->with('course', 'teacher', 'topic')
            ->orderBy('starts_at')
            ->limit(6)
            ->get()
            ->reject(fn (LiveSession $session) => $session->ends_at->isPast())
            ->values();
    }

    /**
     * Quiz progress per course: published quizzes, quizzes passed and average score.
     *
     * @return array<int, array{total: int, passed: int, average: ?int}>
     */
    #[Computed]
    public function progress(): array
    {
        $quizCourse = Quiz::published()
            ->join('topics', 'topics.id', '=', 'quizzes.topic_id')
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            ->whereIn('subjects.course_id', $this->courses->modelKeys())
            ->where(fn ($query) => $query->whereNull('subjects.year_group')->orWhere('subjects.year_group', $this->child->year_group))
            ->pluck('subjects.course_id', 'quizzes.id');

        $attempts = $this->child->quizAttempts()
            ->whereNotNull('completed_at')
            ->whereIn('quiz_id', $quizCourse->keys())
            ->get(['quiz_id', 'percentage', 'passed']);

        return $this->courses->mapWithKeys(function (Course $course) use ($quizCourse, $attempts): array {
            $quizIds = $quizCourse->filter(fn ($courseId) => $courseId === $course->id)->keys();
            $courseAttempts = $attempts->whereIn('quiz_id', $quizIds);

            return [$course->id => [
                'total' => $quizIds->count(),
                'passed' => $courseAttempts->where('passed', true)->unique('quiz_id')->count(),
                'average' => $courseAttempts->isEmpty() ? null : (int) round($courseAttempts->avg('percentage')),
            ]];
        })->all();
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
            ->limit(4)
            ->get();
    }
};
?>

@php
    $child = $this->child;
    $active = $child->hasActiveSubscription();
    $next = $this->upcomingSessions->first();
    $later = $this->upcomingSessions->slice(1);
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $subjectStyle = fn (string $area) => match ($area) {
        'Mathematics' => ['icon' => '➗', 'tile' => 'from-brand-500 to-brand-700'],
        'Biology' => ['icon' => '🧬', 'tile' => 'from-lime-500 to-emerald-600'],
        'Chemistry' => ['icon' => '⚗️', 'tile' => 'from-accent-500 to-rose-500'],
        'Physics' => ['icon' => '⚡', 'tile' => 'from-sky-500 to-indigo-600'],
        default => ['icon' => '📘', 'tile' => 'from-slate-500 to-slate-700'],
    };
@endphp

<div class="mx-auto max-w-6xl space-y-10">
    <header>
        <p class="text-lg text-slate-500">{{ now()->format('l j F') }}</p>
        <h2 class="mt-1 text-3xl font-extrabold text-brand-950 sm:text-4xl">{{ $greeting }}, {{ $child->first_name }}! 👋</h2>
    </header>

    @unless ($active)
        <div class="rounded-3xl border-2 border-amber-200 bg-amber-50 p-6 text-amber-900" role="alert">
            <p class="text-xl font-bold">🔒 Your lessons are paused</p>
            <p class="mt-2 text-lg">Ask your parent to choose or renew your Marvel Academy plan, then you can join lessons and take quizzes again.</p>
        </div>
    @endunless

    {{-- Next lesson --}}
    <section aria-labelledby="next-lesson-heading">
        <h3 id="next-lesson-heading" class="mb-4 text-xl font-bold text-brand-950">Your next lesson</h3>

        @if ($next)
            @php $style = $subjectStyle($next->course->subject_area); @endphp
            <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:p-8">
                    <div class="flex size-20 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br {{ $style['tile'] }} text-4xl shadow-sm" aria-hidden="true">{{ $style['icon'] }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-base font-semibold text-brand-700">{{ $next->course->key_stage->label() }} {{ $next->course->title }}</p>
                        <p class="mt-1 text-2xl leading-snug font-bold text-slate-900">{{ $next->topic?->title ?? $next->title }}</p>
                        <p class="mt-2 text-lg text-slate-600">
                            📅 {{ $next->starts_at->format('l j F') }} · ⏰ {{ $next->starts_at->format('g:ia') }}
                            @if ($next->teacher) · 👩‍🏫 {{ $next->teacher->name }} @endif
                        </p>
                    </div>
                    <div class="shrink-0 text-center sm:text-right">
                        @if ($next->isJoinable() && $active)
                            <a href="{{ route('student.sessions.show', $next) }}" class="btn-primary px-8 py-4 text-xl">
                                <span class="size-3 rounded-full bg-red-600 motion-safe:animate-pulse" aria-hidden="true"></span>
                                Join now
                            </a>
                        @else
                            <p class="rounded-2xl bg-brand-50 px-5 py-3 text-lg font-bold text-brand-800">
                                {{ $next->starts_at->isToday() ? 'Today' : 'In '.(int) ceil(now()->floatDiffInDays($next->starts_at)).' '.Str::plural('day', (int) ceil(now()->floatDiffInDays($next->starts_at))) }}
                            </p>
                            <a href="{{ route('student.sessions.show', $next) }}" wire:navigate class="mt-3 inline-block text-base font-semibold text-brand-700 hover:underline">See lesson details</a>
                        @endif
                    </div>
                </div>
                @if ($next->topic)
                    <div class="border-t border-slate-100 bg-slate-50 px-6 py-4 text-base text-slate-600 sm:px-8">
                        💡 Get ready: try the question pack for this topic before the lesson.
                        <a href="{{ route('student.courses.show', $next->course) }}" wire:navigate class="font-semibold text-brand-700 hover:underline">Open course</a>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-3xl bg-white p-8 text-center text-lg text-slate-600 ring-1 ring-slate-200">
                🌱 No lessons booked yet. Check back soon!
            </div>
        @endif
    </section>

    {{-- Courses --}}
    <section aria-labelledby="courses-heading">
        <h3 id="courses-heading" class="mb-4 text-xl font-bold text-brand-950">My subjects</h3>
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($this->courses as $course)
                @php
                    $style = $subjectStyle($course->subject_area);
                    $stats = $this->progress[$course->id] ?? ['total' => 0, 'passed' => 0, 'average' => null];
                    $percent = $stats['total'] > 0 ? (int) round($stats['passed'] / $stats['total'] * 100) : 0;
                @endphp
                <a href="{{ route('student.courses.show', $course) }}" wire:navigate wire:key="course-{{ $course->id }}" class="group overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-3 focus-visible:outline-accent-500">
                    <div class="flex items-center gap-4 bg-linear-to-br {{ $style['tile'] }} px-6 py-5 text-white">
                        <span class="text-4xl" aria-hidden="true">{{ $style['icon'] }}</span>
                        <div>
                            <p class="text-sm font-semibold text-white/85">{{ $course->key_stage->label() }}</p>
                            <p class="text-2xl font-extrabold">{{ $course->title }}</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between text-base">
                            <span class="font-semibold text-slate-700">Quizzes passed</span>
                            <span class="font-bold text-slate-900">{{ $stats['passed'] }} / {{ $stats['total'] }}</span>
                        </div>
                        <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" aria-label="{{ $course->title }} progress">
                            <div class="h-full rounded-full bg-linear-to-r {{ $style['tile'] }}" style="width: {{ max($percent, 3) }}%"></div>
                        </div>
                        <p class="mt-4 text-base text-slate-600">
                            @if ($stats['average'] !== null)
                                Average score: <span class="font-bold text-slate-900">{{ $stats['average'] }}%</span>
                            @else
                                Take your first quiz to see your score ✨
                            @endif
                        </p>
                        <p class="mt-4 text-base font-bold text-brand-700 group-hover:underline">Open {{ $course->title }} →</p>
                    </div>
                </a>
            @empty
                <p class="text-lg text-slate-600">You are not on any courses yet. Ask your parent to choose them.</p>
            @endforelse
        </div>
    </section>

    <div class="grid gap-8 lg:grid-cols-2">
        {{-- Coming up --}}
        <section aria-labelledby="coming-up-heading" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            <div class="flex items-center justify-between">
                <h3 id="coming-up-heading" class="text-xl font-bold text-brand-950">Coming up</h3>
                <a href="{{ route('student.timetable') }}" wire:navigate class="text-base font-semibold text-brand-700 hover:underline">Timetable</a>
            </div>
            <ul class="mt-4 space-y-3">
                @forelse ($later as $session)
                    <li wire:key="later-{{ $session->id }}">
                        <a href="{{ route('student.sessions.show', $session) }}" wire:navigate class="flex items-center gap-4 rounded-2xl p-3 hover:bg-slate-50">
                            <span class="flex size-14 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-50 text-brand-800">
                                <span class="text-xs font-bold uppercase">{{ $session->starts_at->format('M') }}</span>
                                <span class="text-xl leading-none font-extrabold">{{ $session->starts_at->format('j') }}</span>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-lg font-semibold text-slate-900">{{ $session->course->title }}: {{ $session->topic?->title ?? $session->title }}</span>
                                <span class="block text-base text-slate-500">{{ $session->starts_at->format('l, g:ia') }}</span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="text-lg text-slate-500">Nothing else booked yet.</li>
                @endforelse
            </ul>
        </section>

        {{-- Results --}}
        <section aria-labelledby="results-heading" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            <h3 id="results-heading" class="text-xl font-bold text-brand-950">My latest quiz scores</h3>
            <ul class="mt-4 space-y-3">
                @forelse ($this->recentResults as $attempt)
                    @php $cheer = $attempt->percentage >= 80 ? ['🌟', 'Brilliant!'] : ($attempt->passed ? ['👍', 'Well done!'] : ['💪', 'Keep practising']); @endphp
                    <li wire:key="result-{{ $attempt->id }}">
                        <a href="{{ route('student.quizzes.result', [$attempt->quiz, $attempt]) }}" wire:navigate class="flex items-center gap-4 rounded-2xl p-3 hover:bg-slate-50">
                            <span class="text-3xl" aria-hidden="true">{{ $cheer[0] }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-lg font-semibold text-slate-900">{{ $attempt->quiz->title }}</span>
                                <span class="block text-base text-slate-500">{{ $cheer[1] }} · {{ $attempt->completed_at->diffForHumans() }}</span>
                            </span>
                            <span @class(['shrink-0 rounded-xl px-3 py-1.5 text-lg font-extrabold', 'bg-emerald-50 text-emerald-700' => $attempt->passed, 'bg-amber-50 text-amber-700' => ! $attempt->passed])>{{ $attempt->percentage }}%</span>
                        </a>
                    </li>
                @empty
                    <li class="text-lg text-slate-500">Finish a topic quiz to see your scores here. You've got this! 🚀</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
