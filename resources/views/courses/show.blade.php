@use('App\Support\Money')

<x-layouts::app :title="$course->key_stage->label().' '.$course->title" :description="$course->summary">
    <section class="bg-brand-950 py-16 text-white lg:py-20">
        <div class="container-page grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <nav aria-label="Breadcrumb" class="text-sm text-brand-200">
                    <a href="{{ route('courses.index') }}" wire:navigate class="hover:text-white">Courses</a>
                    <span aria-hidden="true" class="mx-2">/</span>
                    <span aria-current="page">{{ $course->key_stage->label() }} {{ $course->title }}</span>
                </nav>
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="rounded-full bg-accent-400 px-3 py-1 text-xs font-bold text-brand-950">{{ $course->key_stage->label() }}</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold">{{ $course->subject_area }}</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold">{{ $course->key_stage->yearRange() }}</span>
                </div>
                <h1 class="mt-4 text-4xl font-extrabold sm:text-5xl">{{ $course->key_stage->label() }} {{ $course->title }}</h1>
                <p class="mt-5 max-w-2xl text-lg text-brand-100">{{ $course->summary }}</p>
            </div>

            <aside class="rounded-3xl bg-white p-7 text-slate-700 shadow-xl">
                <p class="text-sm text-slate-500">From</p>
                <p class="font-display text-4xl font-extrabold text-brand-950">{{ Money::format($course->price_per_hour) }}<span class="text-base font-medium text-slate-500"> / hour</span></p>
                <p class="mt-1 text-sm text-slate-500">or {{ Money::format($course->price_monthly) }} a month for {{ $course->hours_per_month }} live hours</p>
                <a href="{{ route('register', ['course' => $course->slug]) }}" class="btn-primary mt-6 w-full">Start 7-day free trial</a>
                <ul class="mt-6 space-y-2 text-sm">
                    <li>✓ Weekly live lessons</li>
                    <li>✓ {{ $course->subjects->sum(fn ($subject) => $subject->topics->sum('quizzes_count')) }} topic quizzes</li>
                    <li>✓ Recordings &amp; downloadable materials</li>
                </ul>
            </aside>
        </div>
    </section>

    <section class="py-16 lg:py-20">
        <div class="container-page grid gap-12 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-extrabold text-brand-950">About this course</h2>
                <p class="mt-4 leading-relaxed whitespace-pre-line text-slate-600">{{ $course->description }}</p>

                <h2 class="mt-12 text-2xl font-extrabold text-brand-950">What your child will learn</h2>
                <div class="mt-6 space-y-4">
                    @foreach ($course->subjects as $subject)
                        <details class="group rounded-2xl border border-slate-200 bg-white p-5 open:shadow-sm" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-brand-950">
                                {{ $subject->name }}
                                <span class="text-sm font-normal text-slate-500">{{ $subject->topics->count() }} {{ Str::plural('topic', $subject->topics->count()) }}</span>
                            </summary>
                            <ul class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm text-slate-600">
                                @foreach ($subject->topics as $topic)
                                    <li class="flex justify-between gap-4">
                                        <span>{{ $topic->title }}</span>
                                        @if ($topic->quizzes_count > 0)
                                            <span class="shrink-0 text-xs font-medium text-brand-700">Quiz included</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endforeach
                </div>
            </div>

            <div class="space-y-10">
                @if ($course->teachers->isNotEmpty())
                    <div>
                        <h2 class="text-lg font-bold text-brand-950">Your tutors</h2>
                        <ul class="mt-4 space-y-4">
                            @foreach ($course->teachers as $teacher)
                                <li class="flex items-center gap-4">
                                    @if ($avatar = $teacher->teacherProfile?->getFirstMediaUrl('avatar', 'thumb'))
                                        <img src="{{ $avatar }}" alt="" class="size-12 rounded-full object-cover">
                                    @else
                                        <span class="flex size-12 items-center justify-center rounded-full bg-brand-100 font-bold text-brand-800">{{ Str::of($teacher->name)->explode(' ')->map(fn ($part) => $part[0])->take(2)->implode('') }}</span>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $teacher->name }}</p>
                                        <p class="text-sm text-slate-500">{{ $teacher->teacherProfile?->headline }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($upcomingSessions->isNotEmpty())
                    <div>
                        <h2 class="text-lg font-bold text-brand-950">Upcoming live sessions</h2>
                        <ul class="mt-4 space-y-3">
                            @foreach ($upcomingSessions as $session)
                                <li class="rounded-xl bg-slate-50 p-4 text-sm">
                                    <p class="font-semibold text-slate-800">{{ $session->title }}</p>
                                    <p class="mt-1 text-slate-500">{{ $session->starts_at->format('l j F, g:ia') }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($relatedCourses->isNotEmpty())
        <section class="bg-slate-50 py-16">
            <div class="container-page">
                <h2 class="text-2xl font-extrabold text-brand-950">More {{ $course->key_stage->label() }} courses</h2>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relatedCourses as $related)
                        <x-site.course-card :course="$related" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts::app>
