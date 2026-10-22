<footer class="bg-brand-950 text-brand-100">
    <div class="container-page grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <x-site.logo class="size-9" />
                <span class="font-display text-lg font-extrabold text-white">Marvel Academy</span>
            </a>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-brand-200">
                Live online tutoring for Key Stage 3 and GCSE students, taught by qualified, DBS-checked UK teachers.
            </p>
            <ul class="mt-6 flex gap-3" aria-label="Social media">
                @foreach (config('marvel.social') as $network => $url)
                    <li>
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="flex size-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-accent-500 hover:text-brand-950">
                            <span class="sr-only">{{ ucfirst($network) }}</span>
                            <x-site.social-icon :network="$network" class="size-4.5" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <nav class="lg:col-span-2" aria-label="Explore">
            <h2 class="text-sm font-semibold text-white">Explore</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('courses.index') }}" class="hover:text-white">Courses</a></li>
                <li><a href="{{ route('how-it-works') }}" class="hover:text-white">How it works</a></li>
                <li><a href="{{ route('home') }}#pricing" class="hover:text-white">Pricing</a></li>
                <li><a href="{{ route('about') }}" class="hover:text-white">About us</a></li>
            </ul>
        </nav>

        <nav class="lg:col-span-2" aria-label="Account">
            <h2 class="text-sm font-semibold text-white">Account</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('login') }}" class="hover:text-white">Log in</a></li>
                <li><a href="{{ route('register') }}" class="hover:text-white">Start free trial</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-white">Help &amp; FAQs</a></li>
            </ul>
        </nav>

        <div class="lg:col-span-4">
            <h2 class="text-sm font-semibold text-white">Contact</h2>
            <address class="mt-4 space-y-2.5 text-sm not-italic">
                <p>{{ config('marvel.contact.address') }}</p>
                <p><a href="tel:{{ str_replace(' ', '', config('marvel.contact.phone')) }}" class="hover:text-white">{{ config('marvel.contact.phone') }}</a></p>
                <p><a href="mailto:{{ config('marvel.contact.email') }}" class="hover:text-white">{{ config('marvel.contact.email') }}</a></p>
                <p class="text-brand-300">{{ config('marvel.contact.hours') }}</p>
            </address>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-page flex flex-col items-center justify-between gap-4 py-6 text-xs text-brand-300 sm:flex-row">
            <p>&copy; {{ now()->year }} Marvel Academy Ltd. All rights reserved.</p>
            <ul class="flex gap-6">
                <li><a href="{{ route('legal.terms') }}" class="hover:text-white">Terms &amp; Conditions</a></li>
                <li><a href="{{ route('legal.privacy') }}" class="hover:text-white">Privacy Policy</a></li>
            </ul>
        </div>
    </div>
</footer>
