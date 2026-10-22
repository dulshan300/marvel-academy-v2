<?php

use App\Actions\Children\UpdateChild;
use App\Models\Child;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Edit child details')] class extends Component
{
    #[Locked]
    public Child $child;

    public string $first_name = '';

    public string $last_name = '';

    public string $date_of_birth = '';

    public ?int $year_group = null;

    public string $school_name = '';

    public string $student_email = '';

    public string $student_password = '';

    public function mount(Child $child): void
    {
        $this->authorize('update', $child);

        $this->child = $child->load('student', 'activeCourses');

        $this->first_name = $child->first_name;
        $this->last_name = $child->last_name;
        $this->date_of_birth = $child->date_of_birth?->format('Y-m-d') ?? '';
        $this->year_group = $child->year_group;
        $this->school_name = $child->school_name ?? '';
        $this->student_email = $child->student?->email ?? '';
    }

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
            'student_email' => $this->child->student
                ? ['required', 'string', 'lowercase', 'email:rfc', 'max:255', Rule::unique(User::class, 'email')->ignore($this->child->student_id)]
                : ['nullable'],
            'student_password' => ['nullable', 'string', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'student_email' => 'student login email',
            'student_password' => 'new password',
        ];
    }

    public function changesKeyStage(): bool
    {
        return $this->year_group !== null
            && Child::keyStageForYear((int) $this->year_group) !== $this->child->key_stage;
    }

    public function save(UpdateChild $updateChild): void
    {
        $this->authorize('update', $this->child);

        $this->student_email = mb_strtolower(trim($this->student_email));

        $validated = $this->validate();

        $keyStageChanged = $updateChild->handle($this->child, [
            ...$validated,
            'date_of_birth' => $validated['date_of_birth'] ?: null,
            'school_name' => $validated['school_name'] ?: null,
            'student_password' => $validated['student_password'] ?: null,
        ]);

        if ($keyStageChanged && $this->child->hasActiveSubscription()) {
            session()->flash('status', "{$this->child->first_name}'s details are saved. They are now in {$this->child->key_stage->label()}, so please choose their new courses.");
            $this->redirectRoute('parent.children.courses', $this->child, navigate: true);

            return;
        }

        session()->flash('status', "{$this->child->first_name}'s details have been updated.");
        $this->redirectRoute('parent.dashboard', navigate: true);
    }
};
?>

<div class="max-w-3xl">
    <form wire:submit="save" class="space-y-8" novalidate>
        <x-portal.card title="About {{ $child->first_name }}">
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
                    <select id="year_group" wire:model.live="year_group" class="form-input">
                        @foreach (range(7, 11) as $year)
                            <option value="{{ $year }}">Year {{ $year }} ({{ Child::keyStageForYear($year)->label() }})</option>
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

            @if ($this->changesKeyStage())
                <div class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-800" role="status">
                    Year {{ $year_group }} is {{ Child::keyStageForYear((int) $year_group)->label() }}.
                    @if ($child->activeCourses->isNotEmpty())
                        Saving will remove {{ $child->first_name }}'s current {{ $child->key_stage->label() }} {{ Str::plural('course', $child->activeCourses->count()) }}
                        ({{ $child->activeCourses->map(fn ($course) => $course->title)->join(', ', ' and ') }}), and you can then choose new ones.
                    @endif
                </div>
            @endif
        </x-portal.card>

        @if ($child->student)
            <x-portal.card title="Student login">
                <p class="mb-5 text-sm text-slate-600">{{ $child->first_name }} uses these details to log in to the student portal. Leave the password blank to keep the current one.</p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="student_email" class="form-label">Login email</label>
                        <input id="student_email" type="email" wire:model="student_email" class="form-input" autocomplete="off">
                        @error('student_email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="student_password" class="form-label">New password</label>
                        <input id="student_password" type="password" wire:model="student_password" class="form-input" autocomplete="new-password">
                        @error('student_password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-portal.card>
        @endif

        <div class="flex justify-end gap-3">
            <a href="{{ route('parent.dashboard') }}" wire:navigate class="btn-ghost text-slate-700">Cancel</a>
            <button type="submit" class="btn-primary">
                <span wire:loading.remove wire:target="save">Save changes</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
