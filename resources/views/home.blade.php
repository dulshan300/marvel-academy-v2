@use('App\Support\Money')

<x-layouts::app>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-brand-950 text-white">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -top-32 -right-24 size-[32rem] rounded-full bg-brand-600/40 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-20 size-[26rem] rounded-full bg-accent-500/20 blur-3xl"></div>
        </div>

        <div class="container-page relative grid items-center gap-14 py-20 lg:grid-cols-2 lg:py-28">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-medium text-brand-100 ring-1 ring-white/15">
                    <span class="size-2 rounded-full bg-accent-400"></span>
                    Live online tutoring for KS3 &amp; GCSE
                </p>
                <h1 class="mt-6 text-4xl leading-[1.1] font-extrabold sm:text-5xl lg:text-6xl">
                    Confidence today.<br>
                    <span class="text-accent-400">Top grades</span> tomorrow.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-brand-100">
                    Small-group live lessons with qualified UK teachers, topic quizzes with instant feedback, and progress updates that keep parents in the loop.
                </p>

                <div class="mt-9 flex flex-wrap gap-4">
                    <a href="{{ route('register') }}" class="btn-primary px-7 py-3.5 text-base">Get started free</a>
                    <a href="{{ route('courses.index') }}" wire:navigate class="btn-ghost px-7 py-3.5 text-base text-white">Explore courses</a>
                </div>

                <dl class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/10 pt-8">
                    <div>
                        <dt class="text-sm text-brand-200">Key stages</dt>
                        <dd class="font-display text-2xl font-extrabold">KS3 &amp; GCSE</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-brand-200">Tutors</dt>
                        <dd class="font-display text-2xl font-extrabold">DBS checked</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-brand-200">Free trial</dt>
                        <dd class="font-display text-2xl font-extrabold">7 days</dd>
                    </div>
                </dl>
            </div>

            <div class="relative hidden lg:block" aria-hidden="true">
                <div class="rounded-3xl bg-white p-6 text-slate-700 shadow-2xl ring-1 ring-white/10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Next live lesson</p>
                            <p class="mt-1 font-display text-lg font-bold text-brand-950">GCSE Maths · Quadratic equations</p>
                        </div>
                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600">LIVE 17:30</span>
                    </div>
                    <div class="mt-5 aspect-video rounded-2xl bg-linear-to-br from-brand-700 to-brand-900 p-6">
                        <div class="flex h-full items-center justify-center">
                            <div class="flex size-16 items-center justify-center rounded-full bg-white/95 shadow-lg">
                                <svg class="ml-1 size-7 text-brand-800" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-4">
                        <div class="rounded-xl bg-emerald-50 p-4">
                            <p class="text-xs font-medium text-emerald-700">Last quiz score</p>
                            <p class="font-display text-2xl font-extrabold text-emerald-800">92%</p>
                        </div>
                        <div class="rounded-xl bg-brand-50 p-4">
                            <p class="text-xs font-medium text-brand-700">Topics mastered</p>
                            <p class="font-display text-2xl font-extrabold text-brand-800">18 / 24</p>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-6 -left-8 flex items-center gap-3 rounded-2xl bg-white px-5 py-4 text-sm shadow-xl">
                    <x-site.stars :rating="5" />
                    <span class="font-semibold text-slate-800">Loved by UK families</span>
                </div>
            </div>
        </div>
    </section>

    {{-- What we offer --}}
    <section class="py-20 lg:py-28" aria-labelledby="offer-heading">
        <div class="container-page">
            <x-site.section-heading eyebrow="What we offer" title="Everything your child needs to succeed" id="offer-heading">
                A complete learning loop: learn live, practise with quizzes, and track progress together.
            </x-site.section-heading>

            @php
                $offers = [
                    ['title' => 'Live interactive classes', 'body' => 'Small-group lessons streamed live, with Q&A and recordings to rewatch any time.', 'icon' => 'M15 10l4.55-2.28A1 1 0 0 1 21 8.62v6.76a1 1 0 0 1-1.45.9L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z'],
                    ['title' => 'Topic MCQ quizzes', 'body' => 'Multiple-choice checks after every topic with instant marking and explanations.', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['title' => 'Expert UK tutors', 'body' => 'Qualified, DBS-checked teachers who know the AQA, Edexcel and OCR specifications.', 'icon' => 'M12 14l9-5-9-5-9 5 9 5Zm0 0v6m-6-9.5v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5'],
                    ['title' => 'Parent progress tracking', 'body' => 'Scores, attendance and upcoming sessions in your dashboard, plus regular email updates.', 'icon' => 'M3 3v18h18M7 15l4-4 3 3 5-6'],
                ];
            @endphp

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($offers as $offer)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $offer['icon'] }}"/></svg>
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-brand-950">{{ $offer['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $offer['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="scroll-mt-20 bg-slate-50 py-20 lg:py-28" aria-labelledby="pricing-heading">
        <div class="container-page">
            <x-site.section-heading eyebrow="Pricing" title="Simple, transparent pricing" id="pricing-heading">
                Priced per child, per month. Every plan starts with a 7-day free trial and you can cancel any time.
            </x-site.section-heading>

            <div class="mt-14 grid gap-8 lg:grid-cols-3">
                @foreach ($plans as $plan)
                    <div @class([
                        'relative flex flex-col rounded-3xl p-8',
                        'bg-brand-900 text-white shadow-2xl ring-4 ring-accent-400/60 lg:-translate-y-3' => $plan->is_featured,
                        'bg-white shadow-sm ring-1 ring-slate-200' => ! $plan->is_featured,
                    ])>
                        @if ($plan->is_featured)
                            <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-accent-400 px-4 py-1 text-xs font-bold text-brand-950">Most popular</span>
                        @endif

                        <h3 @class(['text-xl font-bold', 'text-brand-950' => ! $plan->is_featured])>{{ $plan->name }}</h3>
                        <p @class(['mt-2 text-sm', 'text-brand-200' => $plan->is_featured, 'text-slate-500' => ! $plan->is_featured])>{{ $plan->description }}</p>

                        <p class="mt-6 flex items-baseline gap-1">
                            <span class="font-display text-5xl font-extrabold">{{ Money::format($plan->price_monthly) }}</span>
                            <span @class(['text-sm', 'text-brand-200' => $plan->is_featured, 'text-slate-500' => ! $plan->is_featured])>/month</span>
                        </p>
                        <p @class(['mt-1 text-sm font-medium', 'text-accent-300' => $plan->is_featured, 'text-brand-700' => ! $plan->is_featured])>
                            {{ $plan->subjects_included }} {{ Str::plural('subject', $plan->subjects_included) }} · {{ $plan->hours_per_month }} hours · just {{ Money::format($plan->hourlyRate()) }} per hour
                        </p>

                        <ul class="mt-8 flex-1 space-y-3 text-sm">
                            @foreach ($plan->features ?? [] as $feature)
                                <li class="flex gap-3">
                                    <svg @class(['size-5 shrink-0', 'text-accent-400' => $plan->is_featured, 'text-emerald-500' => ! $plan->is_featured]) viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('register', ['plan' => $plan->slug]) }}" @class(['mt-8 w-full', 'btn-primary' => $plan->is_featured, 'btn-secondary' => ! $plan->is_featured])>
                            Start {{ $plan->trial_days }}-day free trial
                        </a>
                    </div>
                @endforeach
            </div>

            <p class="mt-10 text-center text-sm text-slate-500">
                Prefer to pay as you go? Individual subjects start from {{ Money::format(2500) }} per hour. Sibling discounts available.
            </p>
        </div>
    </section>

    {{-- How it works --}}
    <section class="py-20 lg:py-28" aria-labelledby="how-heading">
        <div class="container-page">
            <x-site.section-heading eyebrow="How it works" title="Up and running in three steps" id="how-heading">
                Built for busy families: sign up in minutes and your child can join their first live lesson this week.
            </x-site.section-heading>

            @php
                $steps = [
                    ['title' => 'Register & subscribe', 'body' => 'Create your parent account, choose a plan and start your free trial.'],
                    ['title' => 'Add your child & choose courses', 'body' => 'Add each child, pick their key stage and select the courses they need.'],
                    ['title' => 'Attend live & track progress', 'body' => 'Your child joins live sessions and takes topic quizzes while you follow their results.'],
                ];
            @endphp

            <ol class="mt-14 grid gap-8 md:grid-cols-3">
                @foreach ($steps as $step)
                    <li class="relative rounded-2xl bg-linear-to-b from-brand-50 to-white p-8 ring-1 ring-brand-100">
                        <span class="flex size-12 items-center justify-center rounded-full bg-brand-800 font-display text-lg font-extrabold text-white">{{ $loop->iteration }}</span>
                        <h3 class="mt-6 text-xl font-bold text-brand-950">{{ $step['title'] }}</h3>
                        <p class="mt-3 leading-relaxed text-slate-600">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="mt-10 text-center">
                <a href="{{ route('how-it-works') }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-900">See the full guide for parents and students &rarr;</a>
            </div>
        </div>
    </section>

    {{-- Top rated courses --}}
    <section class="bg-slate-50 py-20 lg:py-28" aria-labelledby="courses-heading">
        <div class="container-page">
            <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-end">
                <x-site.section-heading eyebrow="Top rated courses" title="Popular KS3 and GCSE courses" align="left" id="courses-heading" />
                <a href="{{ route('courses.index') }}" wire:navigate class="btn-secondary">View all courses</a>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($featuredCourses as $course)
                    <x-site.course-card :course="$course" wire:key="course-{{ $course->id }}" />
                @empty
                    <p class="text-slate-500">New courses are coming soon.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-24">
        <div class="container-page">
            <div class="relative overflow-hidden rounded-3xl bg-linear-to-r from-brand-800 to-brand-600 px-8 py-14 text-center text-white shadow-xl sm:px-16">
                <div class="pointer-events-none absolute -top-16 -right-16 size-64 rounded-full bg-accent-400/30 blur-2xl" aria-hidden="true"></div>
                <h2 class="relative text-3xl font-extrabold sm:text-4xl">Try Marvel Academy free for 7 days</h2>
                <p class="relative mx-auto mt-4 max-w-2xl text-lg text-brand-100">
                    Join a live lesson, try the topic quizzes and see the parent dashboard. No charge until your trial ends.
                </p>
                <div class="relative mt-8 flex flex-wrap justify-center gap-4">
                    <a href="{{ route('register') }}" class="btn-primary px-8 py-3.5 text-base">Start your free trial</a>
                    <a href="{{ route('contact') }}" wire:navigate class="btn-ghost px-8 py-3.5 text-base text-white">Talk to our team</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    @if ($testimonials->isNotEmpty())
        <section class="bg-brand-950 py-20 text-white lg:py-28" aria-labelledby="testimonials-heading">
            <div class="container-page">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold tracking-wide text-accent-400 uppercase">Happy students say</p>
                    <h2 id="testimonials-heading" class="mt-2 text-3xl font-extrabold sm:text-4xl">Real results from real families</h2>
                </div>

                <div
                    x-data="{
                        active: 0,
                        total: {{ $testimonials->count() }},
                        timer: null,
                        go(index) { this.active = (index + this.total) % this.total },
                        start() { this.timer = setInterval(() => this.go(this.active + 1), 7000) },
                        stop() { clearInterval(this.timer) },
                    }"
                    x-init="start()"
                    @mouseenter="stop()"
                    @mouseleave="start()"
                    @keydown.left="go(active - 1)"
                    @keydown.right="go(active + 1)"
                    class="relative mx-auto mt-14 max-w-3xl"
                    role="region"
                    aria-roledescription="carousel"
                    aria-label="Testimonials"
                >
                    <div class="grid">
                        @foreach ($testimonials as $testimonial)
                            <figure
                                x-show="active === {{ $loop->index }}"
                                @if (! $loop->first) x-cloak @endif
                                x-transition:enter="transition duration-500"
                                x-transition:enter-start="opacity-0 translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="col-start-1 row-start-1 rounded-3xl bg-white/5 p-8 text-center ring-1 ring-white/10 sm:p-12"
                                aria-roledescription="slide"
                                aria-label="{{ $loop->iteration }} of {{ $loop->count }}"
                            >
                                <x-site.stars :rating="$testimonial->rating" class="justify-center" />
                                <blockquote class="mt-6 text-xl leading-relaxed text-brand-50 sm:text-2xl">
                                    &ldquo;{{ $testimonial->content }}&rdquo;
                                </blockquote>
                                <figcaption class="mt-8">
                                    <p class="font-semibold text-white">{{ $testimonial->author_name }}</p>
                                    <p class="mt-1 flex items-center justify-center gap-2 text-sm text-brand-200">
                                        {{ $testimonial->author_role }}
                                        @if ($testimonial->is_verified)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/15 px-2 py-0.5 text-xs font-medium text-emerald-300">
                                                <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                                Verified
                                            </span>
                                        @endif
                                    </p>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>

                    <div class="mt-8 flex items-center justify-center gap-6">
                        <button type="button" @click="go(active - 1)" class="flex size-11 items-center justify-center rounded-full bg-white/10 hover:bg-white/20">
                            <span class="sr-only">Previous testimonial</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="flex gap-2">
                            @foreach ($testimonials as $testimonial)
                                <button
                                    type="button"
                                    @click="go({{ $loop->index }})"
                                    :class="active === {{ $loop->index }} ? 'w-8 bg-accent-400' : 'w-2.5 bg-white/30'"
                                    class="h-2.5 rounded-full transition-all"
                                    :aria-current="active === {{ $loop->index }}"
                                >
                                    <span class="sr-only">Show testimonial {{ $loop->iteration }}</span>
                                </button>
                            @endforeach
                        </div>
                        <button type="button" @click="go(active + 1)" class="flex size-11 items-center justify-center rounded-full bg-white/10 hover:bg-white/20">
                            <span class="sr-only">Next testimonial</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Newsletter --}}
    <section class="py-20 lg:py-24" aria-labelledby="newsletter-heading">
        <div class="container-page">
            <div class="grid items-center gap-10 rounded-3xl bg-accent-300/30 px-8 py-12 ring-1 ring-accent-400/40 lg:grid-cols-2 lg:px-14">
                <div>
                    <h2 id="newsletter-heading" class="text-3xl font-extrabold text-brand-950">Free revision tips, every fortnight</h2>
                    <p class="mt-3 text-slate-700">Exam technique, study plans and term-time updates for KS3 and GCSE families. Unsubscribe any time.</p>
                </div>
                <livewire:newsletter-signup />
            </div>
        </div>
    </section>
</x-layouts::app>
