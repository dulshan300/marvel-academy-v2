<?php

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')] #[Title('Log in')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Sign the user in and send them to the portal for their role.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => __('This account has been deactivated. Please contact support.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->redirectIntended(route('dashboard', absolute: false));
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
};
?>

<div>
    <h1 class="text-3xl font-extrabold text-brand-950">Welcome back</h1>
    <p class="mt-2 text-slate-600">Log in to your parent, student, teacher or admin account.</p>

    <form wire:submit="login" class="mt-8 space-y-5" novalidate>
        <div>
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" wire:model="email" autocomplete="username" autofocus required class="form-input" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email') <p id="email-error" class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password" required class="form-input" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password') <p id="password-error" class="form-error">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember" class="size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
            Keep me signed in
        </label>

        <button type="submit" class="btn-secondary w-full py-3.5">
            <span wire:loading.remove wire:target="login">Log in</span>
            <span wire:loading wire:target="login">Signing in…</span>
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-600">
        New to Marvel Academy?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-900">Start your free trial</a>
    </p>
</div>
