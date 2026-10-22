<?php

use App\Models\TeacherProfile;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::portal')] #[Title('My profile')] class extends Component
{
    use WithFileUploads;

    public string $headline = '';

    public string $bio = '';

    public string $qualifications = '';

    public int $years_experience = 0;

    public string $phone = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $avatar = null;

    public function mount(): void
    {
        $profile = $this->profile;

        $this->headline = (string) $profile->headline;
        $this->bio = (string) $profile->bio;
        $this->qualifications = (string) $profile->qualifications;
        $this->years_experience = $profile->years_experience;
        $this->phone = (string) auth()->user()->phone;
    }

    #[Computed]
    public function profile(): TeacherProfile
    {
        return auth()->user()->teacherProfile()->firstOrCreate([]);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'qualifications' => ['nullable', 'string', 'max:1000'],
            'years_experience' => ['required', 'integer', 'between:0,60'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9 +()-]{7,32}$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $this->profile->update(collect($validated)->only(['headline', 'bio', 'qualifications', 'years_experience'])->all());
        auth()->user()->update(['phone' => $validated['phone'] ?: null]);

        if ($this->avatar !== null) {
            $this->profile->addMedia($this->avatar->getRealPath())
                ->usingFileName('avatar.'.mb_strtolower($this->avatar->getClientOriginalExtension()))
                ->toMediaCollection('avatar');
            $this->avatar = null;
        }

        session()->flash('status', 'Profile updated.');
        $this->redirectRoute('teacher.profile', navigate: true);
    }
};
?>

<form wire:submit="save" class="max-w-3xl space-y-6" novalidate>
    <x-portal.card title="Public profile">
        <div class="flex items-center gap-5">
            @if ($avatar)
                <img src="{{ $avatar->temporaryUrl() }}" alt="" class="size-20 rounded-full object-cover">
            @elseif ($url = $this->profile->getFirstMediaUrl('avatar'))
                <img src="{{ $url }}" alt="" class="size-20 rounded-full object-cover">
            @else
                <span class="flex size-20 items-center justify-center rounded-full bg-brand-100 text-2xl font-bold text-brand-800">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            @endif
            <div>
                <label for="avatar" class="form-label">Photo</label>
                <input id="avatar" type="file" wire:model="avatar" accept="image/jpeg,image/png,image/webp" class="text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-semibold file:text-brand-700">
                @error('avatar') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="headline" class="form-label">Headline</label>
                <input id="headline" type="text" wire:model="headline" placeholder="e.g. GCSE Chemistry Tutor" class="form-input">
                @error('headline') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="bio" class="form-label">About you</label>
                <textarea id="bio" rows="5" wire:model="bio" class="form-input"></textarea>
                @error('bio') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="qualifications" class="form-label">Qualifications</label>
                <input id="qualifications" type="text" wire:model="qualifications" class="form-input">
                @error('qualifications') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="years_experience" class="form-label">Years of teaching experience</label>
                <input id="years_experience" type="number" min="0" wire:model="years_experience" class="form-input">
                @error('years_experience') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="form-label">Phone (staff only)</label>
                <input id="phone" type="tel" wire:model="phone" class="form-input">
                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-portal.card>

    <div class="flex justify-end">
        <button type="submit" class="btn-primary">Save profile</button>
    </div>
</form>
