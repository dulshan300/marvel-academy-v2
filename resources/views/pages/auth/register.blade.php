<?php

use App\Enums\Role;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::auth')] #[Title('Create your parent account')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $terms = false;

    #[Url, Locked]
    public string $plan = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9 +()-]{7,32}$/'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'terms.accepted' => 'Please accept the terms and privacy policy to continue.',
            'phone.regex' => 'Please enter a valid UK phone number.',
        ];
    }

    public function register(): void
    {
        $this->email = mb_strtolower(trim($this->email));

        $validated = $this->validate();

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?: null,
                'password' => $validated['password'],
            ]);

            $user->assignRole(Role::Parent->value);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        session()->regenerate();

        if (Plan::active()->where('slug', $this->plan)->exists()) {
            session()->put('checkout.plan', $this->plan);
        }

        $this->redirect(route('dashboard', absolute: false));
    }
};
?>

<div>
    <h1 class="text-3xl font-extrabold text-brand-950">Create your parent account</h1>
    <p class="mt-2 text-slate-600">Start with a 7-day free trial. You will add your children and choose courses next.</p>

    <form wire:submit="register" class="mt-8 space-y-5" novalidate>
        <div>
            <label for="name" class="form-label">Your full name</label>
            <input id="name" type="text" wire:model.blur="name" autocomplete="name" autofocus class="form-input" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name') <p id="name-error" class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="email" class="form-label">Email address</label>
                <input id="email" type="email" wire:model.blur="email" autocomplete="email" class="form-input" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <p id="email-error" class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="form-label">Mobile (optional)</label>
                <input id="phone" type="tel" wire:model.blur="phone" autocomplete="tel" class="form-input" @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                @error('phone') <p id="phone-error" class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" wire:model="password" autocomplete="new-password" class="form-input" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password') <p id="password-error" class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" class="form-input">
            </div>
        </div>

        <div>
            <label class="flex items-start gap-2 text-sm text-slate-600">
                <input type="checkbox" wire:model="terms" class="mt-0.5 size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                <span>I am a parent or guardian aged 18+ and agree to the <a href="{{ route('legal.terms') }}" target="_blank" class="font-semibold text-brand-700 underline">terms</a> and <a href="{{ route('legal.privacy') }}" target="_blank" class="font-semibold text-brand-700 underline">privacy policy</a>.</span>
            </label>
            @error('terms') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary w-full py-3.5">
            <span wire:loading.remove wire:target="register">Create account</span>
            <span wire:loading wire:target="register">Creating your account…</span>
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-600">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-900">Log in</a>
    </p>
</div>
