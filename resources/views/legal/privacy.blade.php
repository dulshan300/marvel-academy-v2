<x-layouts::app title="Privacy Policy">
    <article class="container-page max-w-3xl py-16 lg:py-20">
        <h1 class="text-4xl font-extrabold text-brand-950">Privacy Policy</h1>
        <p class="mt-2 text-sm text-slate-500">Last updated {{ now()->format('j F Y') }}</p>

        <div class="mt-10 space-y-8 leading-relaxed text-slate-600">
            <section>
                <h2 class="text-xl font-bold text-brand-950">Who we are</h2>
                <p class="mt-3">Marvel Academy (company no. {{ config('marvel.contact.company_number') }}) is the data controller for personal information collected through this website and our portals. We process personal data in line with the UK GDPR and the Data Protection Act 2018.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-brand-950">What we collect</h2>
                <p class="mt-3">Parent contact and billing details, the name, year group and school of each registered child, attendance and quiz results, and messages you send us. Card details are handled by Stripe and never stored on our servers.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-brand-950">How we use it</h2>
                <p class="mt-3">To provide lessons and quizzes, send session reminders and progress updates, take payments, respond to enquiries and, if you opt in, send our newsletter. We never sell personal data.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-brand-950">Children's data</h2>
                <p class="mt-3">Children's accounts are created and managed by a parent or guardian. Student data is visible only to the parent, assigned tutors and authorised staff.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-brand-950">Your rights</h2>
                <p class="mt-3">You can request access to, correction or deletion of your data, or object to its processing, by emailing <a class="font-semibold text-brand-700" href="mailto:{{ config('marvel.contact.email') }}">{{ config('marvel.contact.email') }}</a>. You may also complain to the Information Commissioner's Office (ico.org.uk).</p>
            </section>
        </div>
    </article>
</x-layouts::app>
