<x-layouts::app title="How it works" description="How Marvel Academy works for parents and students: onboarding, live sessions, materials and quiz feedback.">
    <section class="bg-brand-950 py-16 text-white lg:py-20">
        <div class="container-page max-w-4xl text-center">
            <p class="text-sm font-semibold tracking-wide text-accent-400 uppercase">How it works</p>
            <h1 class="mt-2 text-4xl font-extrabold sm:text-5xl">One module a month, mastered</h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-brand-100">
                Our purpose is to keep students up to date with their work and maximise their knowledge, with intensive live sessions that combine theory and exam preparation.
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
                    ['Prepare with the question pack', 'Before each lesson you receive a pack of past exam questions. Try as many as you can so you know where you need help.'],
                    ['Join live sessions', 'Click "Join live" when the session starts. Your tutor works through every question, then teaches the full theory.'],
                    ['Watch recordings', 'Missed a lesson or want a recap? Recordings are added to your course after each session.'],
                    ['Download materials', 'Question packs, lecture notes and worksheets for every module are ready to download.'],
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
            <x-site.section-heading eyebrow="Our teaching approach" title="Theory and exam technique, together">
                Monthly intensive classes are more effective than regular weekly tutoring, giving room to cover more material and give students an advantage over their peers.
            </x-site.section-heading>

            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['One module a month', 'Each subject covers one module of the syllabus every month, taught live twice a month in 2.5-hour sessions on alternate Saturdays.'],
                    ['Question pack first', 'Students receive past exam questions in advance to gauge their understanding and spot where they need to improve.'],
                    ['Every question reviewed', 'In class, tutors work through each question and explain how to score maximum marks, then confirm understanding with a full theory lesson.'],
                    ['Finished before exams', 'Our 10-month programme (September to June) completes the curriculum before the exams, with look-ahead classes in July and August.'],
                ] as [$title, $body])
                    <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <span class="flex size-10 items-center justify-center rounded-full bg-accent-500 font-display font-extrabold text-brand-950">{{ $loop->iteration }}</span>
                        <h3 class="mt-4 text-lg font-bold text-brand-950">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-12 text-center">
                <a href="{{ route('register') }}" class="btn-primary px-8 py-3.5 text-base">Start your free trial</a>
            </div>
        </div>
    </section>
</x-layouts::app>
