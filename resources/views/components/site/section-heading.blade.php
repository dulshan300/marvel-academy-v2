@props([
    'eyebrow' => null,
    'title',
    'align' => 'center',
])

<div {{ $attributes->class(['max-w-2xl', 'mx-auto text-center' => $align === 'center']) }}>
    @if ($eyebrow)
        <p class="text-sm font-semibold tracking-wide text-accent-600 uppercase">{{ $eyebrow }}</p>
    @endif
    <h2 class="mt-2 text-3xl font-extrabold text-brand-950 sm:text-4xl">{{ $title }}</h2>
    @if ($slot->isNotEmpty())
        <p class="mt-4 text-lg leading-relaxed text-slate-600">{{ $slot }}</p>
    @endif
</div>
