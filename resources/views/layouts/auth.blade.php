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
    <body class="min-h-screen bg-white font-sans text-slate-700 antialiased">
        <div class="grid min-h-screen lg:grid-cols-2">
            <div class="flex flex-col px-6 py-8 sm:px-12">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5">
                    <x-site.logo class="size-9" />
                    <span class="font-display text-lg font-extrabold text-brand-900">Marvel Academy</span>
                </a>

                <main class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-12">
                    {{ $slot }}
                </main>
            </div>

            <aside class="relative hidden overflow-hidden bg-brand-950 lg:block" aria-hidden="true">
                <div class="absolute -top-24 -right-24 size-[28rem] rounded-full bg-brand-600/40 blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 size-96 rounded-full bg-accent-500/20 blur-3xl"></div>
                <div class="relative flex h-full flex-col justify-end p-14 text-white">
                    <x-site.stars :rating="5" />
                    <blockquote class="mt-6 max-w-lg font-display text-2xl leading-snug font-bold">
                        &ldquo;The weekly progress emails mean I always know exactly where my daughter is. Her Maths grade went from a 4 to a 7.&rdquo;
                    </blockquote>
                    <p class="mt-4 text-brand-200">Sarah K., parent of a Year 10 student</p>
                </div>
            </aside>
        </div>
    </body>
</html>
