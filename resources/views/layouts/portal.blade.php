@props(['title' => null])

<!DOCTYPE html>
<html lang="en-GB">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>{{ $title ? $title.' | '.config('app.name') : config('app.name') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-700 antialiased" x-data="{ sidebar: false }">
        <div class="flex min-h-screen">
            {{-- Sidebar --}}
            <div x-show="sidebar" x-cloak class="fixed inset-0 z-40 bg-brand-950/50 lg:hidden" @click="sidebar = false"></div>
            <aside
                :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-brand-950 text-brand-100 transition-transform lg:static lg:translate-x-0"
            >
                <a href="{{ route('home') }}" class="flex h-18 items-center gap-2.5 px-6">
                    <x-site.logo class="size-9" />
                    <span class="font-display text-lg font-black tracking-wide text-white uppercase">Marvel Academy</span>
                </a>

                <x-portal.nav class="flex-1 overflow-y-auto px-4 py-4" />

                <div class="border-t border-white/10 p-4">
                    <div class="flex items-center gap-3 px-2">
                        <span class="flex size-10 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white">
                            {{ Str::of(auth()->user()->name)->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-brand-300">{{ auth()->user()->primaryRole()?->label() }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-brand-200 hover:bg-white/5 hover:text-white">Log out</button>
                    </form>
                </div>
            </aside>

            {{-- Main --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-30 flex h-18 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-8">
                    <button type="button" class="rounded-lg p-2 text-slate-600 lg:hidden" @click="sidebar = true">
                        <span class="sr-only">Open navigation</span>
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <h1 class="truncate font-display text-xl font-extrabold text-brand-950">{{ $title }}</h1>
                    @isset($actions)
                        <div class="ml-auto flex items-center gap-3">{{ $actions }}</div>
                    @endisset
                </header>

                <main class="flex-1 px-4 py-8 sm:px-8">
                    @if (session('status'))
                        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
