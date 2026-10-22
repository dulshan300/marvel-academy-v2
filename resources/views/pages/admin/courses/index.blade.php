<?php

use App\Enums\KeyStage;
use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::portal')] #[Title('Courses')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $stage = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function courses(): LengthAwarePaginator
    {
        $term = '%'.mb_strtolower(trim($this->search)).'%';

        return Course::query()
            ->withCount(['subjects', 'topics', 'enrolments'])
            ->when(KeyStage::tryFrom($this->stage), fn ($query, $keyStage) => $query->where('key_stage', $keyStage))
            ->when(trim($this->search) !== '', fn ($query) => $query->whereRaw('LOWER(title) LIKE ?', [$term]))
            ->orderBy('key_stage')
            ->orderBy('title')
            ->orderBy('id')
            ->paginate(20);
    }

    public function togglePublished(int $courseId): void
    {
        $course = Course::findOrFail($courseId);
        $course->update(['is_published' => ! $course->is_published]);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row">
        <div class="flex flex-1 gap-3">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search courses…" class="form-input max-w-sm" aria-label="Search courses">
            <select wire:model.live="stage" class="form-input max-w-48" aria-label="Key stage">
                <option value="">All key stages</option>
                @foreach (\App\Enums\KeyStage::cases() as $keyStage)
                    <option value="{{ $keyStage->value }}">{{ $keyStage->label() }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('admin.courses.create') }}" wire:navigate class="btn-primary">New course</a>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Course</th>
                    <th class="px-5 py-3">Structure</th>
                    <th class="px-5 py-3">Enrolments</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->courses as $course)
                    <tr wire:key="course-{{ $course->id }}">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-800">{{ $course->key_stage->label() }} {{ $course->title }}</p>
                            <p class="text-xs text-slate-500">{{ $course->slug }}</p>
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $course->subjects_count }} subjects · {{ $course->topics_count }} topics</td>
                        <td class="px-5 py-4 text-slate-600">{{ $course->enrolments_count }}</td>
                        <td class="px-5 py-4">
                            <button type="button" wire:click="togglePublished({{ $course->id }})" title="Toggle published">
                                <x-portal.badge :tone="$course->is_published ? 'green' : 'slate'">{{ $course->is_published ? 'Published' : 'Draft' }}</x-portal.badge>
                            </button>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.courses.edit', $course) }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-900">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No courses found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->courses->links() }}
</div>
