<?php

use App\Actions\Children\RegisterChild;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Add a child')] class extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $date_of_birth = '';

    public ?int $year_group = null;

    public string $school_name = '';

    public string $student_email = '';

    public string $student_password = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'date_of_birth' => ['nullable', 'date', 'before:-8 years', 'after:-20 years'],
            'year_group' => ['required', 'integer', 'between:7,11'],
            'school_name' => ['nullable', 'string', 'max:150'],
            'student_email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:'.User::class.',email'],
            'student_password' => ['required', 'string', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'student_email' => 'student login email',
            'student_password' => 'student password',
        ];
    }

    public function save(RegisterChild $registerChild): void
    {
        $this->student_email = mb_strtolower(trim($this->student_email));

        $validated = $this->validate();

        $child = $registerChild->handle(auth()->user(), [
            ...$validated,
            'date_of_birth' => $validated['date_of_birth'] ?: null,
            'school_name' => $validated['school_name'] ?: null,
        ]);

        session()->flash('status', "{$child->first_name} has been added. Now choose their plan and courses.");

        $this->redirectRoute('parent.children.subscribe', $child, navigate: true);
    }
};
?>

<div class="max-w-3xl">
    <form wire:submit="save" class="space-y-8" novalidate>
        <x-portal.card title="About your child">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="first_name" class="form-label">First name</label>
                    <input id="first_name" type="text" wire:model="first_name" class="form-input" autocomplete="off">
                    @error('first_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="last_name" class="form-label">Last name</label>
                    <input id="last_name" type="text" wire:model="last_name" class="form-input" autocomplete="off">
                    @error('last_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="year_group" class="form-label">School year</label>
                    <select id="year_group" wire:model="year_group" class="form-input">
                        <option value="">Select a year</option>
                        @foreach (range(7, 11) as $year)
                            <option value="{{ $year }}">Year {{ $year }} ({{ \App\Models\Child::keyStageForYear($year)->label() }})</option>
                        @endforeach
                    </select>
                    @error('year_group') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="date_of_birth" class="form-label">Date of birth (optional)</label>
                    <input id="date_of_birth" type="date" wire:model="date_of_birth" class="form-input">
                    @error('date_of_birth') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="school_name" class="form-label">School (optional)</label>
                    <input id="school_name" type="text" wire:model="school_name" class="form-input">
                    @error('school_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-portal.card>

        <x-portal.card title="Student login">
            <p class="mb-5 text-sm text-slate-600">Your child uses these details to log in to their student portal to join live sessions, download materials and take quizzes.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="student_email" class="form-label">Login email</label>
                    <input id="student_email" type="email" wire:model="student_email" class="form-input" autocomplete="off">
                    @error('student_email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="student_password" class="form-label">Password</label>
                    <input id="student_password" type="password" wire:model="student_password" class="form-input" autocomplete="new-password">
                    @error('student_password') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-portal.card>

        <div class="flex justify-end gap-3">
            <a href="{{ route('parent.dashboard') }}" wire:navigate class="btn-ghost text-slate-700">Cancel</a>
            <button type="submit" class="btn-primary">
                <span wire:loading.remove wire:target="save">Continue to plans</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
