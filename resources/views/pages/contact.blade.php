<?php

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Faq;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Contact us')] class extends Component
{
    #[Validate('required|string|min:2|max:120')]
    public string $name = '';

    #[Validate('required|email:rfc|max:255')]
    public string $email = '';

    #[Validate(['nullable', 'string', 'max:32', 'regex:/^[0-9 +()-]{7,32}$/'], message: ['regex' => 'Please enter a valid UK phone number.'])]
    public string $phone = '';

    #[Validate('required|string|min:3|max:150')]
    public string $subject = '';

    #[Validate('required|string|min:10|max:5000')]
    public string $message = '';

    public bool $sent = false;

    /**
     * @return Collection<string, Collection<int, Faq>>
     */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::published()->get()->groupBy('category');
    }

    public function send(): void
    {
        $validated = $this->validate();

        $key = 'contact:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('message', 'You have sent several messages recently. Please try again in a few minutes.');

            return;
        }

        RateLimiter::hit($key, 600);

        $contactMessage = ContactMessage::create([
            ...$validated,
            'phone' => $validated['phone'] ?: null,
        ]);

        Mail::to(config('marvel.contact.email'))->queue(new ContactMessageReceived($contactMessage));

        $this->reset('name', 'email', 'phone', 'subject', 'message');
        $this->sent = true;
    }
};
?>

<div>
    <section class="bg-brand-950 py-16 text-white lg:py-20">
        <div class="container-page">
            <p class="text-sm font-semibold tracking-wide text-accent-400 uppercase">Contact us</p>
            <h1 class="mt-2 text-4xl font-extrabold sm:text-5xl">We are here to help</h1>
            <p class="mt-4 max-w-2xl text-lg text-brand-100">Questions about courses, billing or your account? Our UK-based team usually replies within one working day.</p>
        </div>
    </section>

    <section class="py-16 lg:py-20">
        <div class="container-page grid gap-12 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @if ($sent)
                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-10 text-center" role="status">
                        <h2 class="text-2xl font-extrabold text-emerald-900">Thank you, your message is on its way</h2>
                        <p class="mt-3 text-emerald-800">We have received your enquiry and will be in touch shortly.</p>
                        <button type="button" wire:click="$set('sent', false)" class="btn-secondary mt-6">Send another message</button>
                    </div>
                @else
                    <form wire:submit="send" class="grid gap-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2 sm:p-10" novalidate>
                        @foreach ([
                            ['name', 'Your name', 'text', 'name'],
                            ['email', 'Email address', 'email', 'email'],
                            ['phone', 'Phone (optional)', 'tel', 'tel'],
                            ['subject', 'Subject', 'text', 'off'],
                        ] as [$field, $label, $type, $autocomplete])
                            <div>
                                <label for="contact-{{ $field }}" class="form-label">{{ $label }}</label>
                                <input
                                    id="contact-{{ $field }}"
                                    type="{{ $type }}"
                                    wire:model.blur="{{ $field }}"
                                    autocomplete="{{ $autocomplete }}"
                                    class="form-input"
                                    @error($field) aria-invalid="true" aria-describedby="contact-{{ $field }}-error" @enderror
                                >
                                @error($field) <p id="contact-{{ $field }}-error" class="form-error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach

                        <div class="sm:col-span-2">
                            <label for="contact-message" class="form-label">Message</label>
                            <textarea id="contact-message" rows="6" wire:model.blur="message" class="form-input" @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror></textarea>
                            @error('message') <p id="contact-message-error" class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center justify-between gap-4 sm:col-span-2">
                            <p class="text-xs text-slate-500">We use your details only to reply to this enquiry. See our <a href="{{ route('legal.privacy') }}" class="underline">privacy policy</a>.</p>
                            <button type="submit" class="btn-primary shrink-0 px-8">
                                <span wire:loading.remove wire:target="send">Send message</span>
                                <span wire:loading wire:target="send">Sending…</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <aside class="space-y-6">
                <div class="rounded-3xl bg-brand-50 p-8">
                    <h2 class="text-lg font-bold text-brand-950">UK support</h2>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div>
                            <dt class="font-semibold text-slate-800">Phone</dt>
                            <dd><a href="tel:{{ str_replace(' ', '', config('marvel.contact.phone')) }}" class="text-brand-700">{{ config('marvel.contact.phone') }}</a></dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-800">Email</dt>
                            <dd><a href="mailto:{{ config('marvel.contact.email') }}" class="text-brand-700">{{ config('marvel.contact.email') }}</a></dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-800">Opening hours</dt>
                            <dd class="text-slate-600">{{ config('marvel.contact.hours') }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-800">Registered office</dt>
                            <dd class="text-slate-600">{{ config('marvel.contact.address') }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </section>

    @if ($this->faqs->isNotEmpty())
        <section class="bg-slate-50 py-16 lg:py-20" aria-labelledby="faq-heading">
            <div class="container-page max-w-4xl">
                <x-site.section-heading eyebrow="FAQs" title="Frequently asked questions" id="faq-heading" />

                <div class="mt-12 space-y-10">
                    @foreach ($this->faqs as $category => $faqs)
                        <div wire:key="faq-category-{{ Str::slug($category) }}">
                            <h3 class="text-sm font-semibold tracking-wide text-brand-700 uppercase">{{ $category }}</h3>
                            <div class="mt-4 divide-y divide-slate-200 rounded-2xl bg-white ring-1 ring-slate-200">
                                @foreach ($faqs as $faq)
                                    <details class="group p-5" wire:key="faq-{{ $faq->id }}">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-brand-950">
                                            {{ $faq->question }}
                                            <svg class="size-5 shrink-0 text-slate-400 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                                        </summary>
                                        <p class="mt-3 leading-relaxed text-slate-600">{{ $faq->answer }}</p>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
