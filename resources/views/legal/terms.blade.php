<x-layouts::app title="Terms & Conditions">
    <article class="container-page max-w-3xl py-16 lg:py-20">
        <h1 class="text-4xl font-extrabold text-brand-950">Terms &amp; Conditions</h1>
        <p class="mt-2 text-sm text-slate-500">Last updated {{ now()->format('j F Y') }}</p>

        <div class="mt-10 space-y-8 leading-relaxed text-slate-600">
            <section>
                <h2 class="text-xl font-bold text-brand-950">1. About these terms</h2>
                <p class="mt-3">These terms apply to subscriptions to Marvel Academy online tutoring services provided by Marvel Academy, a company registered in England and Wales (company no. {{ config('marvel.contact.company_number') }}), whose registered office is at {{ config('marvel.contact.address') }}. By creating an account you agree to them on behalf of yourself and any child you register.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-brand-950">2. Accounts</h2>
                <p class="mt-3">Parent or guardian accounts must be held by an adult aged 18 or over. You are responsible for the student logins you create and for keeping all passwords secure.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-brand-950">3. Subscriptions, trials and payment</h2>
                <p class="mt-3">Subscriptions are billed monthly in advance through our payment provider, Stripe. Free trials convert to a paid subscription at the end of the trial unless cancelled beforehand. Discount codes apply as described when issued.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-brand-950">4. Cancellation</h2>
                <p class="mt-3">You may cancel at any time from your parent dashboard. Access continues until the end of the current billing period. Under the Consumer Contracts Regulations 2013 you may cancel within 14 days of first purchase for a refund of any unused portion.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-brand-950">5. Acceptable use</h2>
                <p class="mt-3">Live sessions, recordings and materials are for the personal use of registered students only and must not be shared, recorded or redistributed.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-brand-950">6. Contact</h2>
                <p class="mt-3">Questions about these terms? Email <a class="font-semibold text-brand-700" href="mailto:{{ config('marvel.contact.email') }}">{{ config('marvel.contact.email') }}</a>.</p>
            </section>
        </div>
    </article>
</x-layouts::app>
