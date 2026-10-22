<?php

use App\Actions\Children\AssignCourses;
use App\Models\Child;
use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Change courses')] class extends Component
{
    #[Locked]
    public Child $child;

    /** @var array<int, int> */
    public array $courseIds = [];

    public function mount(Child $child): void
    {
        $this->authorize('update', $child);

        $this->child = $child;

        if (! $child->hasActiveSubscription() || $child->plan === null) {
            $this->redirectRoute('parent.children.subscribe', $child, navigate: true);

            return;
        }

        $this->courseIds = $child->activeCourses()->pluck('courses.id')->all();
    }

    /**
     * @return Collection<int, Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return Course::published()
            ->where('key_stage', $this->child->key_stage)
            ->orderBy('title')
            ->get();
    }

    public function updatedCourseIds(): void
    {
        $this->courseIds = array_values(array_map('intval', $this->courseIds));
    }

    public function save(AssignCourses $assignCourses): void
    {
        $this->authorize('update', $this->child);

        $assignCourses->handle($this->child, $this->child->plan, $this->courseIds);

        session()->flash('status', "{$this->child->first_name}'s courses have been updated.");

        $this->redirectRoute('parent.dashboard', navigate: true);
    }
};
?>

<div class="max-w-3xl">
    <x-portal.card :title="$child->first_name.'\'s courses'">
        <p class="mb-5 text-sm text-slate-600">
            The {{ $child->plan->name }} plan includes up to {{ $child->plan->subjects_included }} {{ Str::plural('course', $child->plan->subjects_included) }}.
            Need more? <a href="{{ route('parent.billing') }}" wire:navigate class="font-semibold text-brand-700 underline">Change plan</a>.
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($this->courses as $course)
                @php($checked = in_array($course->id, $courseIds, true))
                @php($disabled = ! $checked && count($courseIds) >= $child->plan->subjects_included)
                <label wire:key="course-{{ $course->id }}" @class([
                    'flex items-center gap-3 rounded-xl border p-4',
                    'border-brand-600 bg-brand-50' => $checked,
                    'border-slate-200' => ! $checked,
                    'cursor-not-allowed opacity-50' => $disabled,
                    'cursor-pointer' => ! $disabled,
                ])>
                    <input type="checkbox" wire:model.live="courseIds" value="{{ $course->id }}" @disabled($disabled) class="size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                    <span class="font-semibold text-slate-800">{{ $course->key_stage->label() }} {{ $course->title }}</span>
                </label>
            @endforeach
        </div>
        @error('courses') <p class="form-error">{{ $message }}</p> @enderror

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('parent.dashboard') }}" wire:navigate class="btn-ghost text-slate-700">Cancel</a>
            <button type="button" wire:click="save" class="btn-primary">Save courses</button>
        </div>
    </x-portal.card>
</div>
