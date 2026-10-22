<?php

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|email:rfc|max:255')]
    public string $email = '';

    public bool $subscribed = false;

    public function subscribe(): void
    {
        $this->email = mb_strtolower(trim($this->email));

        $this->validate();

        $key = 'newsletter:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Too many attempts. Please try again in a minute.');

            return;
        }

        RateLimiter::hit($key, 60);

        $subscriber = NewsletterSubscriber::firstOrNew(['email' => $this->email]);
        $subscriber->source = 'website';
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        $this->reset('email');
        $this->subscribed = true;
    }
};
?>

<div>
    @if ($subscribed)
        <div class="flex items-center gap-3 rounded-2xl bg-white px-5 py-4 text-emerald-800 shadow-sm" role="status">
            <svg class="size-6 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
            <p class="font-medium">Thanks for subscribing! Look out for your first email soon.</p>
        </div>
    @else
        <form wire:submit="subscribe" class="flex flex-col gap-3 sm:flex-row" novalidate>
            <div class="flex-1">
                <label for="newsletter-email" class="sr-only">Email address</label>
                <input
                    id="newsletter-email"
                    type="email"
                    wire:model.live.blur="email"
                    placeholder="you@example.co.uk"
                    autocomplete="email"
                    class="form-input"
                    @error('email') aria-invalid="true" aria-describedby="newsletter-email-error" @enderror
                >
                @error('email')
                    <p id="newsletter-email-error" class="form-error">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-secondary h-12.5 shrink-0 px-7">
                <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                <span wire:loading wire:target="subscribe">Subscribing…</span>
            </button>
        </form>
    @endif
</div>
