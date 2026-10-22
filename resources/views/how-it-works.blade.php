<x-layouts::app title="How it works" description="How Marvel Academy works for parents and students: onboarding, live sessions, materials and quiz feedback.">
    <section class="bg-brand-950 py-16 text-white lg:py-20">
        <div class="container-page max-w-4xl text-center">
            <p class="text-sm font-semibold tracking-wide text-accent-400 uppercase">How it works</p>
            <h1 class="mt-2 text-4xl font-extrabold sm:text-5xl">A learning routine that fits family life</h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-brand-100">
                From sign-up to exam day, here is exactly what to expect, whether you are a parent setting things up or a student joining lessons.
            </p>
        </div>
    </section>

    @php
        $journeys = [
            'parents' => [
                'heading' => 'For parents',
                'intro' => 'You stay in control of subscriptions, courses and progress from one dashboard.',
                'steps' => [
                    ['Create your account', 'Register as a parent and choose a plan. Your 7-day free trial starts straight away and you will not be charged until it ends.'],
                    ['Add your children', 'Add each child with their year group and key stage. We create a simple student login for them.'],
                    ['Choose courses', 'Assign the courses each child needs. You can change them any time within your plan.'],
                    ['Get reminders', 'We email you seven days before each live session with preparation materials, then two days before with joining instructions.'],
                    ['Track progress', 'Quiz scores, attendance and upcoming sessions appear in your dashboard, and we send regular progress emails.'],
                ],
            ],
            'students' => [
                'heading' => 'For students',
                'intro' => 'A focused portal with everything you need for each lesson in one place.',
                'steps' => [
                    ['Log in to your portal', 'Use the student login your parent set up. Your timetable shows every upcoming live session.'],
                    ['Join live sessions', 'Click "Join live" when the session starts. Ask questions and work through problems with your tutor.'],
                    ['Watch recordings', 'Missed a lesson or want a recap? Recordings are added to your course after each session.'],
                    ['Download materials', 'Preparation guides, worksheets and notes for every topic are ready to download.'],
                    ['Take topic quizzes', 'Finish each topic with a multiple-choice quiz. You get your score instantly, with explanations for every question.'],
                ],
            ],
        ];
    @endphp

    <section class="py-16 lg:py-24" x-data="{ tab: 'parents' }">
        <div class="container-page">
            <div class="flex justify-center">
                <div class="inline-flex rounded-2xl bg-slate-100 p-1.5" role="tablist" aria-label="Choose a guide">
                    @foreach ($journeys as $key => $journey)
                        <button
                            type="button"
                            role="tab"
                            id="tab-{{ $key }}"
                            aria-controls="panel-{{ $key }}"
                            :aria-selected="tab === '{{ $key }}'"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-white text-brand-800 shadow-sm' : 'text-slate-600 hover:text-brand-700'"
                            class="rounded-xl px-6 py-2.5 text-sm font-semibold transition"
                        >{{ $journey['heading'] }}</button>
                    @endforeach
                </div>
            </div>

            @foreach ($journeys as $key => $journey)
                <div id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" x-show="tab === '{{ $key }}'" @if (! $loop->first) x-cloak @endif class="mx-auto mt-12 max-w-3xl">
                    <p class="text-center text-lg text-slate-600">{{ $journey['intro'] }}</p>
                    <ol class="relative mt-12 space-y-10 border-l-2 border-brand-100 pl-10">
                        @foreach ($journey['steps'] as [$title, $body])
                            <li class="relative">
                                <span class="absolute top-0 -left-[3.15rem] flex size-10 items-center justify-center rounded-full bg-brand-800 font-display font-extrabold text-white ring-4 ring-white">{{ $loop->iteration }}</span>
                                <h2 class="text-xl font-bold text-brand-950">{{ $title }}</h2>
                                <p class="mt-2 leading-relaxed text-slate-600">{{ $body }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-slate-50 py-16 lg:py-20">
        <div class="container-page">
            <x-site.section-heading eyebrow="The feedback loop" title="Learn, check, improve" />
            <div class="mt-12 grid gap-6 md:grid-cols-4">
                @foreach ([['Live lesson', 'Taught by a subject specialist'], ['Topic quiz', 'Marked instantly'], ['Review', 'Explanations for every answer'], ['Parent update', 'Scores shared automatically']] as [$title, $body])
                    <div class="rounded-2xl bg-white p-6 text-center ring-1 ring-slate-200">
                        <p class="font-display text-lg font-bold text-brand-900">{{ $title }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-12 text-center">
                <a href="{{ route('register') }}" class="btn-primary px-8 py-3.5 text-base">Start your free trial</a>
            </div>
        </div>
    </section>
</x-layouts::app>
