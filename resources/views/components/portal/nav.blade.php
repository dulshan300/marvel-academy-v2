@use('App\Enums\Role')

@php
    $items = match (auth()->user()->primaryRole()) {
        Role::Parent => [
            ['Dashboard', 'parent.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['Add a child', 'parent.children.create', 'M12 4v16m8-8H4'],
            ['Billing & plans', 'parent.billing', 'M3 7h18M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7M3 7l2-3h14l2 3M7 15h4'],
        ],
        Role::Student => [
            ['My learning', 'student.dashboard', 'M12 6v12m-6-6h12'],
        ],
        Role::Teacher => [
            ['Dashboard', 'teacher.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
        ],
        Role::Admin => [
            ['Dashboard', 'admin.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
        ],
        default => [],
    };
@endphp

<nav {{ $attributes }} aria-label="Portal">
    <ul class="space-y-1">
        @foreach ($items as [$label, $route, $icon])
            @continue(! Route::has($route))
            <li>
                <a
                    href="{{ route($route) }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-white/10 text-white' => request()->routeIs($route),
                        'text-brand-200 hover:bg-white/5 hover:text-white' => ! request()->routeIs($route),
                    ])
                    @if (request()->routeIs($route)) aria-current="page" @endif
                >
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
