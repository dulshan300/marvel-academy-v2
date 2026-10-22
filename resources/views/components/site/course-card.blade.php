@use('App\Support\Money')

@props(['course'])

@php
    $palette = match ($course->subject_area) {
        'Mathematics' => 'from-brand-600 to-brand-800',
        'English' => 'from-rose-500 to-rose-700',
        'Science' => 'from-emerald-500 to-teal-700',
        default => 'from-slate-500 to-slate-700',
    };
    $cover = $course->getFirstMediaUrl('cover', 'card');
@endphp

<article {{ $attributes->class('group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg') }}>
    <div class="relative aspect-16/10 overflow-hidden">
        @if ($cover)
            <img src="{{ $cover }}" alt="" class="size-full object-cover" loading="lazy">
        @else
            <div class="flex size-full items-end bg-linear-to-br {{ $palette }} p-5">
                <span class="font-display text-2xl font-extrabold text-white/95">{{ $course->subject_area }}</span>
            </div>
        @endif
        <span class="absolute top-4 left-4 rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-brand-800 shadow-sm">
            {{ $course->key_stage->label() }}
        </span>
    </div>

    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-lg font-bold text-brand-950">
            <a href="{{ route('courses.show', $course) }}" wire:navigate class="after:absolute after:inset-0 focus:outline-none">
                {{ $course->key_stage->label() }} {{ $course->title }}
            </a>
        </h3>
        <p class="mt-2 line-clamp-2 flex-1 text-sm text-slate-600">{{ $course->summary }}</p>

        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
            <span class="text-slate-500">{{ $course->key_stage->yearRange() }}</span>
            <span class="font-semibold text-brand-800">From {{ Money::format($course->price_per_hour) }}/hr</span>
        </div>
    </div>
</article>
