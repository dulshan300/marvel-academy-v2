<x-layouts::app title="About us" description="Founded in 2022, Marvel Academy is an engineer-run tuition academy offering live KS3 and GCSE Maths, Biology, Chemistry and Physics.">
    <section class="bg-brand-950 py-16 text-white lg:py-24">
        <div class="container-page grid items-center gap-12 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold tracking-wide text-accent-400 uppercase">The Marvel Academy team</p>
                <h1 class="mt-2 text-4xl font-extrabold sm:text-5xl">Powered by experts</h1>
                <p class="mt-6 text-lg leading-relaxed text-brand-100">
                    Established in 2022, Marvel Academy is driven by a team of more than 40 skilled engineers and scientists who guide ambitious students through their high school examinations.
                </p>
            </div>
            <dl class="grid grid-cols-2 gap-6">
                @foreach ([['2022', 'Founded in London'], ['40+', 'Engineers & scientists'], ['Years 7–11', 'KS3 and GCSE'], ['4 subjects', 'Maths, Biology, Chemistry, Physics']] as [$value, $label])
                    <div class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10">
                        <dt class="text-sm text-brand-200">{{ $label }}</dt>
                        <dd class="mt-2 font-display text-3xl font-extrabold">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <section class="py-16 lg:py-24">
        <div class="container-page grid gap-12 lg:grid-cols-2">
            <div>
                <h2 class="text-3xl font-extrabold text-brand-950">Our story</h2>
                <div class="mt-4 space-y-4 text-lg leading-relaxed text-slate-600">
                    <p>
                        At the forefront of this pioneering academy, led exclusively by engineers, is Pradeep De Silva, a highly regarded senior civil engineer. Pradeep has provided maths tutoring since 1997, in the UK and internationally, through personalised one-to-one and group sessions covering KS3, GCSE, AS and A-Level.
                    </p>
                    <p>
                        In 2022 Pradeep founded Marvel Academy with the goal of inspiring the next generation of engineers and scientists. Starting with weekend online sessions in Maths, Chemistry, Physics and Biology for Years 7 to 11, the academy quickly saw its students become more inspired and motivated in their studies.
                    </p>
                </div>
            </div>
            <div>
                <h2 class="text-3xl font-extrabold text-brand-950">Our mission</h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">
                    To ensure every student has access to exceptional education without limitations. We created Marvel Academy so students can receive top-quality tuition without exorbitant costs, taught by people who use these subjects in their professional lives every day.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-16 lg:py-24">
        <div class="container-page">
            <x-site.section-heading eyebrow="Our values" title="What guides everything we do" />
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Theory and exam technique', 'Students learn the concepts and how to apply them to score maximum marks.'],
                    ['Practice before every lesson', 'Past-paper question packs help students find their gaps before class.'],
                    ['Affordable excellence', 'Top-quality teaching at a price that works for families.'],
                    ['Inspiring future scientists', 'Learning from working engineers and scientists brings the subjects to life.'],
                ] as [$title, $body])
                    <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <h3 class="text-lg font-bold text-brand-950">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-24">
        <div class="container-page grid items-start gap-12 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <h2 class="text-3xl font-extrabold text-brand-950">Tutor expertise standards</h2>
                <p class="mt-4 text-lg text-slate-600">We use a rigorous selection process to choose our teachers. Every Marvel Academy tutor:</p>
            </div>
            <ul class="grid gap-4 sm:grid-cols-2 lg:col-span-3">
                @foreach ([
                    'Is an engineer or scientist from a leading university',
                    'Has deep subject knowledge in the course they teach',
                    'Teaches to the current UK KS3 and GCSE curriculum',
                    'Combines past-paper practice with full theory lessons',
                    'Is reviewed and rated by families after sessions',
                    'Is committed to inspiring and motivating every student',
                ] as $standard)
                    <li class="flex gap-3 rounded-xl bg-brand-50 p-4 text-sm font-medium text-brand-900">
                        <svg class="size-5 shrink-0 text-brand-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                        {{ $standard }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="bg-brand-50 py-16 lg:py-20">
        <div class="container-page">
            <x-site.section-heading eyebrow="Meet the team" title="Some of the people behind Marvel Academy" />
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['Nalaka Jayantha', 'Mathematics', 'A passionate mathematics educator with a BSc in Civil Engineering and over a decade of maths teaching experience, currently pursuing a PhD in Engineering. Nalaka brings numbers to life with a dynamic, interactive approach.'],
                    ['Dushan Ishanka', 'Biology', 'A Biomedical Science graduate who makes biology engaging and interactive, using modern technology in lessons to keep students motivated and inspired in a safe learning environment.'],
                    ['Sai Brashanthan', 'Chemistry', 'Chemical Engineering undergraduate at Imperial College London.'],
                ] as [$name, $subject, $bio])
                    <article class="rounded-2xl bg-white p-6 ring-1 ring-brand-100">
                        <div class="flex items-center gap-4">
                            <span class="flex size-12 items-center justify-center rounded-full bg-brand-700 font-bold text-white">{{ Str::of($name)->explode(' ')->map(fn ($part) => $part[0])->take(2)->implode('') }}</span>
                            <div>
                                <h3 class="font-bold text-brand-950">{{ $name }}</h3>
                                <p class="text-sm text-brand-700">{{ $subject }}</p>
                            </div>
                        </div>
                        <p class="mt-4 text-sm leading-relaxed text-slate-600">{{ $bio }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts::app>
