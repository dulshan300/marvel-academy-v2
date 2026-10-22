<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts::portal')] class extends Component
{
    #[Locked]
    public ?int $teacherId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $headline = '';

    public string $bio = '';

    public string $qualifications = '';

    public int $years_experience = 0;

    public bool $dbs_checked = false;

    public bool $is_featured = false;

    public bool $is_active = true;

    /** @var array<int, int> */
    public array $courseIds = [];

    public function mount(?User $teacher = null): void
    {
        if (! $teacher?->exists) {
            return;
        }

        abort_unless($teacher->hasRole(Role::Teacher->value), 404);

        $profile = $teacher->teacherProfile;

        $this->teacherId = $teacher->id;
        $this->name = $teacher->name;
        $this->email = $teacher->email;
        $this->is_active = $teacher->is_active;
        $this->headline = (string) $profile?->headline;
        $this->bio = (string) $profile?->bio;
        $this->qualifications = (string) $profile?->qualifications;
        $this->years_experience = $profile?->years_experience ?? 0;
        $this->dbs_checked = (bool) $profile?->dbs_checked;
        $this->is_featured = (bool) $profile?->is_featured;
        $this->courseIds = $teacher->taughtCourses()->pluck('courses.id')->all();
    }

    public function render()
    {
        return $this->view()->title($this->teacherId ? 'Edit teacher' : 'Add teacher');
    }

    /**
     * @return Collection<int, Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return Course::orderBy('key_stage')->orderBy('title')->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->teacherId)],
            'password' => [$this->teacherId ? 'nullable' : 'required', 'string', Password::defaults()],
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'qualifications' => ['nullable', 'string', 'max:1000'],
            'years_experience' => ['required', 'integer', 'between:0,60'],
            'dbs_checked' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'courseIds' => ['array'],
            'courseIds.*' => ['integer', Rule::exists('courses', 'id')],
        ];
    }

    public function save(): void
    {
        $this->authorize(Permission::ManageTeachers->value);

        $this->email = mb_strtolower(trim($this->email));
        $validated = $this->validate();

        $teacher = DB::transaction(function () use ($validated): User {
            $teacher = $this->teacherId ? User::findOrFail($this->teacherId) : new User;

            $teacher->fill(['name' => $validated['name'], 'email' => $validated['email'], 'is_active' => $validated['is_active']]);

            if (filled($validated['password'])) {
                $teacher->password = $validated['password'];
            }

            $teacher->save();
            $teacher->assignRole(Role::Teacher->value);

            $teacher->teacherProfile()->updateOrCreate([], collect($validated)->only(['headline', 'bio', 'qualifications', 'years_experience', 'dbs_checked', 'is_featured'])->all());
            $teacher->taughtCourses()->sync(array_map('intval', $validated['courseIds']));

            return $teacher;
        });

        session()->flash('status', 'Teacher saved.');

        $this->redirectRoute('admin.teachers.edit', $teacher, navigate: true);
    }
};
?>

<form wire:submit="save" class="max-w-4xl space-y-6" novalidate>
    <x-portal.card title="Account">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="form-label">Full name</label>
                <input id="name" type="text" wire:model="name" class="form-input">
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="form-label">Login email</label>
                <input id="email" type="email" wire:model="email" class="form-input">
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="form-label">{{ $teacherId ? 'New password (leave blank to keep)' : 'Password' }}</label>
                <input id="password" type="password" wire:model="password" autocomplete="new-password" class="form-input">
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-end gap-2 pb-3 text-sm"><input type="checkbox" wire:model="is_active" class="size-4 rounded border-slate-300 text-brand-700"> Account active</label>
        </div>
    </x-portal.card>

    <x-portal.card title="Profile">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="headline" class="form-label">Headline</label>
                <input id="headline" type="text" wire:model="headline" class="form-input">
            </div>
            <div class="sm:col-span-2">
                <label for="bio" class="form-label">Bio</label>
                <textarea id="bio" rows="4" wire:model="bio" class="form-input"></textarea>
            </div>
            <div>
                <label for="qualifications" class="form-label">Qualifications</label>
                <input id="qualifications" type="text" wire:model="qualifications" class="form-input">
            </div>
            <div>
                <label for="years_experience" class="form-label">Years of experience</label>
                <input id="years_experience" type="number" min="0" wire:model="years_experience" class="form-input">
                @error('years_experience') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="dbs_checked" class="size-4 rounded border-slate-300 text-brand-700"> Enhanced DBS check verified</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_featured" class="size-4 rounded border-slate-300 text-brand-700"> Feature on the website</label>
        </div>
    </x-portal.card>

    <x-portal.card title="Courses taught">
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->courses as $course)
                <label class="flex items-center gap-2 text-sm" wire:key="course-{{ $course->id }}">
                    <input type="checkbox" wire:model="courseIds" value="{{ $course->id }}" class="size-4 rounded border-slate-300 text-brand-700">
                    {{ $course->key_stage->label() }} {{ $course->title }}
                </label>
            @endforeach
        </div>
    </x-portal.card>

    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.teachers.index') }}" wire:navigate class="btn-ghost text-slate-700">Back</a>
        <button type="submit" class="btn-primary">Save teacher</button>
    </div>
</form>
