@props([
    'title' => null,
    'description' => 'Live online KS3 and GCSE tutoring from qualified UK teachers, with topic quizzes and parent progress tracking.',
])

<!DOCTYPE html>
<html lang="en-GB">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description }}">

        <title>{{ $title ? $title.' | '.config('app.name') : config('app.name').' | Online KS3 & GCSE Tutoring' }}</title>

        <link rel="icon" href="{{ asset('favicon.ico') }}">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-white font-sans text-slate-700 antialiased">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">
            Skip to content
        </a>

        <x-site.header />

        <main id="main" class="flex-1">
            <x-flash />

            {{ $slot }}
        </main>

        <x-site.footer />
    </body>
</html>
