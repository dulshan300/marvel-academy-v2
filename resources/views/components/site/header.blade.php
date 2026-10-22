@php
    $links = [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'Courses', 'route' => 'courses.index'],
        ['label' => 'How it works', 'route' => 'how-it-works'],
        ['label' => 'About us', 'route' => 'about'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
    <nav class="container-page flex h-18 items-center justify-between gap-6" aria-label="Main">
        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5">
            <x-site.logo class="size-9" />
            <span class="font-display text-lg font-black tracking-wide text-brand-700 uppercase">Marvel Academy</span>
        </a>

        <ul class="hidden items-center gap-1 lg:flex">
            @foreach ($links as $link)
                <li>
                    <a
                        href="{{ route($link['route']) }}"
                        wire:navigate
                        @class([
                            'rounded-lg px-3 py-2 text-sm font-medium transition',
                            'text-brand-700 bg-brand-50' => request()->routeIs($link['route'].'*'),
                            'text-slate-600 hover:text-brand-700' => ! request()->routeIs($link['route'].'*'),
                        ])
                        @if (request()->routeIs($link['route'])) aria-current="page" @endif
                    >{{ $link['label'] }}</a>
                </li>
            @endforeach
        </ul>

        <div class="hidden items-center gap-3 lg:flex">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-secondary">My dashboard</a>
            @else
                <a href="{{ route('login') }}" wire:navigate class="text-sm font-semibold text-slate-700 hover:text-brand-700">Log in</a>
                <a href="{{ route('register') }}" class="btn-primary">Start free trial</a>
            @endauth
        </div>

        <button type="button" class="rounded-lg p-2 text-slate-700 lg:hidden" @click="open = ! open" :aria-expanded="open" aria-controls="mobile-menu">
            <span class="sr-only">Toggle menu</span>
            <svg x-show="! open" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            <svg x-show="open" x-cloak class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </nav>

    <div id="mobile-menu" x-show="open" x-cloak x-transition.origin.top class="border-t border-slate-200 bg-white lg:hidden">
        <ul class="container-page flex flex-col gap-1 py-4">
            @foreach ($links as $link)
                <li><a href="{{ route($link['route']) }}" wire:navigate class="block rounded-lg px-3 py-2.5 font-medium text-slate-700 hover:bg-slate-50">{{ $link['label'] }}</a></li>
            @endforeach
            <li class="mt-3 flex flex-col gap-2 border-t border-slate-100 pt-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-secondary">My dashboard</a>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="btn-ghost text-slate-700">Log in</a>
                    <a href="{{ route('register') }}" class="btn-primary">Start free trial</a>
                @endauth
            </li>
        </ul>
    </div>
</header>
