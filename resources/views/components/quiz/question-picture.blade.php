@props(['question'])

@php
    $hasImage = $question->getFirstMedia('image') !== null;
    $src = $hasImage ? route('questions.image', $question) : null;
    $alt = $question->description ? Str::limit($question->description, 150) : 'Picture for question';
@endphp

<div {{ $attributes->class('space-y-3') }} x-data="{ zoomed: false }">
    @if ($hasImage)
        <button type="button" x-on:click="zoomed = true" class="group relative block w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-50" aria-label="Make the picture bigger">
            <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" class="mx-auto max-h-[28rem] w-auto object-contain">
            <span class="absolute right-3 bottom-3 rounded-full bg-white/90 px-3 py-1 text-sm font-semibold text-slate-700 shadow-sm group-hover:bg-white">🔍 Tap to enlarge</span>
        </button>

        <div
            x-show="zoomed"
            x-cloak
            x-transition.opacity
            x-on:keydown.escape.window="zoomed = false"
            x-on:click="zoomed = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Enlarged picture"
        >
            <img src="{{ $src }}" alt="{{ $alt }}" class="max-h-full max-w-full rounded-xl bg-white object-contain">
            <button type="button" class="absolute top-4 right-4 rounded-full bg-white px-4 py-2 text-base font-semibold text-slate-800">Close ✕</button>
        </div>
    @endif

    @if ($question->description)
        <p class="rounded-2xl bg-brand-50 px-5 py-4 text-lg leading-relaxed whitespace-pre-line text-slate-800">{{ $question->description }}</p>
    @endif
</div>
