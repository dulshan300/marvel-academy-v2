@use('App\Enums\Role')

@php
    $items = match (auth()->user()->primaryRole()) {
        Role::Parent => [
            ['Dashboard', 'parent.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['Add a child', 'parent.children.create', 'M12 4v16m8-8H4'],
            ['Billing & plans', 'parent.billing', 'M3 7h18M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7M3 7l2-3h14l2 3M7 15h4'],
        ],
        Role::Student => [
            ['My learning', 'student.dashboard', 'M12 6.25v13m0-13C10.8 5.5 9.2 5 7.5 5S4.2 5.5 3 6.25v13C4.2 18.5 5.8 18 7.5 18s3.3.5 4.5 1.25m0-13C13.2 5.5 14.8 5 16.5 5s3.3.5 4.5 1.25v13C19.8 18.5 18.2 18 16.5 18s-3.3.5-4.5 1.25'],
            ['Timetable', 'student.timetable', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z'],
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
