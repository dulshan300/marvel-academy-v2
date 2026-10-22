<x-layouts::app title="About us" description="The Marvel Academy story, our mission, values and the standards every tutor meets.">
    <section class="bg-brand-950 py-16 text-white lg:py-24">
        <div class="container-page grid items-center gap-12 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold tracking-wide text-accent-400 uppercase">About Marvel Academy</p>
                <h1 class="mt-2 text-4xl font-extrabold sm:text-5xl">Great teaching, wherever you live</h1>
                <p class="mt-6 text-lg leading-relaxed text-brand-100">
                    Marvel Academy was founded by UK classroom teachers who saw how much difference a little extra, expert support could make, and how hard it was for many families to find it locally. We bring that support online, live and affordably.
                </p>
            </div>
            <dl class="grid grid-cols-2 gap-6">
                @foreach ([['100%', 'Qualified, DBS-checked tutors'], ['KS3–GCSE', 'Curriculum-aligned courses'], ['7 days', 'Free trial on every plan'], ['UK-based', 'Support team, Mon–Sat']] as [$value, $label])
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
                <h2 class="text-3xl font-extrabold text-brand-950">Our mission</h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">
                    To help every student in Key Stage 3 and GCSE build real understanding and the confidence that comes with it, by combining expert live teaching with regular, low-stakes practice and honest feedback.
                </p>
            </div>
            <div>
                <h2 class="text-3xl font-extrabold text-brand-950">Our story</h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">
                    What began as evening revision sessions for a handful of Year 11 students has grown into a full online academy. The approach has not changed: small groups, clear explanations, and parents who always know how their child is getting on.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-16 lg:py-24">
        <div class="container-page">
            <x-site.section-heading eyebrow="Our values" title="What guides everything we do" />
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Clarity first', 'Complex ideas explained simply, with worked examples before independent practice.'],
                    ['Every child seen', 'Small groups and quiz data mean nobody slips through the gaps.'],
                    ['Partnership with parents', 'Regular, honest updates so learning continues at home.'],
                    ['Safe by design', 'Safeguarding-trained staff and a secure, ad-free platform.'],
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
                <p class="mt-4 text-lg text-slate-600">Fewer than one in ten applicants join our teaching team. Every Marvel Academy tutor:</p>
            </div>
            <ul class="grid gap-4 sm:grid-cols-2 lg:col-span-3">
                @foreach ([
                    'Holds QTS, a PGCE or a degree in their subject',
                    'Has an enhanced DBS certificate, renewed regularly',
                    'Has taught the current UK specifications (AQA, Edexcel, OCR)',
                    'Completes our safeguarding and online-teaching training',
                    'Is observed and reviewed every term',
                    'Is rated by families after every session',
                ] as $standard)
                    <li class="flex gap-3 rounded-xl bg-brand-50 p-4 text-sm font-medium text-brand-900">
                        <svg class="size-5 shrink-0 text-brand-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                        {{ $standard }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</x-layouts::app>
