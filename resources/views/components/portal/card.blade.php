@props(['title' => null])

<section {{ $attributes->class('rounded-2xl border border-slate-200 bg-white shadow-sm') }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4">
            @if ($title)
                <h2 class="font-display text-lg font-bold text-brand-950">{{ $title }}</h2>
            @endif
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif
    <div class="p-6">
        {{ $slot }}
    </div>
</section>
