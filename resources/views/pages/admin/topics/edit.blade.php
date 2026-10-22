<?php

use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\Topic;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::portal')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public Topic $topic;

    public string $title = '';

    public string $description = '';

    public string $materialTitle = '';

    public string $materialDescription = '';

    public string $materialType = 'pdf';

    public string $materialUrl = '';

    public bool $materialIsPreparatory = false;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $materialFile = null;

    public string $quizTitle = '';

    public function mount(Topic $topic): void
    {
        $this->authorize('manage', Course::class);

        $this->topic = $topic->load('subject.course');
        $this->title = $topic->title;
        $this->description = (string) $topic->description;
    }

    public function render()
    {
        return $this->view()->title($this->topic->title);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Material>
     */
    #[Computed]
    public function materials()
    {
        return $this->topic->materials()->with('media')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Quiz>
     */
    #[Computed]
    public function quizzes()
    {
        return $this->topic->quizzes()->withCount(['questions', 'attempts'])->orderBy('id')->get();
    }

    public function saveTopic(): void
    {
        $this->authorize('manage', Course::class);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->topic->update($validated);

        session()->flash('status', 'Topic saved.');
    }

    public function addMaterial(): void
    {
        $this->authorize('manage', Course::class);

        $type = MaterialType::tryFrom($this->materialType);

        $validated = $this->validate([
            'materialTitle' => ['required', 'string', 'max:255'],
            'materialDescription' => ['nullable', 'string', 'max:2000'],
            'materialType' => ['required', Rule::enum(MaterialType::class)],
            'materialIsPreparatory' => ['boolean'],
            'materialFile' => [Rule::requiredIf($type?->isFile() ?? false), 'nullable', 'file', 'max:51200', 'extensions:'.implode(',', $type?->allowedExtensions() ?: ['pdf']), 'mimes:'.implode(',', $type?->allowedExtensions() ?: ['pdf'])],
            'materialUrl' => [Rule::requiredIf(! ($type?->isFile() ?? true)), 'nullable', 'url:https,http', 'max:2048'],
        ], attributes: [
            'materialTitle' => 'title',
            'materialFile' => 'file',
            'materialUrl' => 'link',
        ]);

        $material = $this->topic->materials()->create([
            'title' => $validated['materialTitle'],
            'description' => $validated['materialDescription'] ?: null,
            'type' => $type,
            'external_url' => $type->isFile() ? null : $validated['materialUrl'],
            'is_preparatory' => $validated['materialIsPreparatory'],
            'position' => (int) $this->topic->materials()->max('position') + 1,
        ]);

        if ($type->isFile() && $this->materialFile !== null) {
            $baseName = str(pathinfo($this->materialFile->getClientOriginalName(), PATHINFO_FILENAME))->slug()->limit(100, '')->toString() ?: 'material';
            $extension = mb_strtolower($this->materialFile->getClientOriginalExtension());

            $material->addMedia($this->materialFile->getRealPath())
                ->usingName($validated['materialTitle'])
                ->usingFileName("{$baseName}.{$extension}")
                ->toMediaCollection('file');
        }

        $this->reset('materialTitle', 'materialDescription', 'materialUrl', 'materialFile', 'materialIsPreparatory');
        unset($this->materials);
    }

    public function togglePreparatory(int $materialId): void
    {
        $this->authorize('manage', Course::class);

        $material = $this->topic->materials()->findOrFail($materialId);
        $material->update(['is_preparatory' => ! $material->is_preparatory]);
        unset($this->materials);
    }

    public function deleteMaterial(int $materialId): void
    {
        $this->authorize('manage', Course::class);

        $this->topic->materials()->findOrFail($materialId)->delete();
        unset($this->materials);
    }

    public function createQuiz(): void
    {
        $this->authorize('manage', Course::class);

        $this->validate(['quizTitle' => ['required', 'string', 'max:255']], attributes: ['quizTitle' => 'quiz title']);

        $quiz = $this->topic->quizzes()->create([
            'title' => $this->quizTitle,
            'pass_mark' => 60,
            'is_published' => false,
        ]);

        $this->redirectRoute('admin.quizzes.edit', $quiz, navigate: true);
    }

    public function deleteQuiz(int $quizId): void
    {
        $this->authorize('manage', Course::class);

        $this->topic->quizzes()->findOrFail($quizId)->delete();
        unset($this->quizzes);
    }
};
?>

<div class="space-y-8">
    <nav class="text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('admin.courses.edit', $topic->subject->course) }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-900">{{ $topic->subject->course->key_stage->label() }} {{ $topic->subject->course->title }}</a>
        <span class="mx-1">/</span> {{ $topic->subject->name }}
    </nav>

    <div class="grid gap-8 xl:grid-cols-2">
        <x-portal.card title="Topic">
            <form wire:submit="saveTopic" class="space-y-4">
                <div>
                    <label for="title" class="form-label">Title</label>
                    <input id="title" type="text" wire:model="title" class="form-input">
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" rows="4" wire:model="description" class="form-input"></textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-secondary">Save topic</button>
            </form>
        </x-portal.card>

        <x-portal.card title="Quizzes">
            <ul class="divide-y divide-slate-100">
                @forelse ($this->quizzes as $quiz)
                    <li wire:key="quiz-{{ $quiz->id }}" class="flex items-center justify-between gap-3 py-3 first:pt-0">
                        <div>
                            <a href="{{ route('admin.quizzes.edit', $quiz) }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-900">{{ $quiz->title }}</a>
                            <p class="text-xs text-slate-500">{{ $quiz->questions_count }} questions · {{ $quiz->attempts_count }} attempts</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-portal.badge :tone="$quiz->is_published ? 'green' : 'slate'">{{ $quiz->is_published ? 'Published' : 'Draft' }}</x-portal.badge>
                            <button type="button" wire:click="deleteQuiz({{ $quiz->id }})" wire:confirm="Delete this quiz and all student attempts?" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                        </div>
                    </li>
                @empty
                    <li class="pb-3 text-sm text-slate-500">No quizzes yet.</li>
                @endforelse
            </ul>
            <form wire:submit="createQuiz" class="mt-4 flex gap-2 border-t border-slate-100 pt-4">
                <input type="text" wire:model="quizTitle" placeholder="New quiz title" class="form-input">
                <button type="submit" class="btn-primary shrink-0">Create quiz</button>
            </form>
            @error('quizTitle') <p class="form-error">{{ $message }}</p> @enderror
        </x-portal.card>
    </div>

    <x-portal.card title="Materials">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-slate-500 uppercase"><tr><th class="pb-2">Title</th><th class="pb-2">Type</th><th class="pb-2">7-day reminder</th><th class="pb-2"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->materials as $material)
                        <tr wire:key="material-{{ $material->id }}">
                            <td class="py-3">
                                <a href="{{ route('materials.download', $material) }}" target="_blank" class="font-medium text-brand-700 hover:text-brand-900">{{ $material->title }}</a>
                                @if ($file = $material->getFirstMedia('file'))
                                    <p class="text-xs text-slate-500">{{ $file->file_name }} · {{ $file->human_readable_size }}</p>
                                @endif
                            </td>
                            <td class="py-3 text-slate-600">{{ $material->type->label() }}</td>
                            <td class="py-3">
                                <button type="button" wire:click="togglePreparatory({{ $material->id }})">
                                    <x-portal.badge :tone="$material->is_preparatory ? 'brand' : 'slate'">{{ $material->is_preparatory ? 'Attached' : 'No' }}</x-portal.badge>
                                </button>
                            </td>
                            <td class="py-3 text-right"><button type="button" wire:click="deleteMaterial({{ $material->id }})" wire:confirm="Delete this material?" class="text-red-600 hover:text-red-800">Delete</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-3 text-slate-500">No materials yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form wire:submit="addMaterial" class="mt-6 grid gap-4 border-t border-slate-100 pt-6 md:grid-cols-2" novalidate>
            <h3 class="font-semibold text-slate-800 md:col-span-2">Add material</h3>
            <div>
                <label for="materialTitle" class="form-label">Title</label>
                <input id="materialTitle" type="text" wire:model="materialTitle" class="form-input">
                @error('materialTitle') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="materialType" class="form-label">Type</label>
                <select id="materialType" wire:model.live="materialType" class="form-input">
                    @foreach (\App\Enums\MaterialType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}{{ $type->isFile() ? ' (upload)' : ' (link)' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                @if (\App\Enums\MaterialType::tryFrom($materialType)?->isFile())
                    <label for="materialFile" class="form-label">File ({{ strtoupper(implode(' or ', \App\Enums\MaterialType::from($materialType)->allowedExtensions())) }}, max 50 MB)</label>
                    <input id="materialFile" type="file" wire:model="materialFile" accept="{{ collect(\App\Enums\MaterialType::from($materialType)->allowedExtensions())->map(fn ($extension) => '.'.$extension)->join(',') }}" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-semibold file:text-brand-700">
                    <div wire:loading wire:target="materialFile" class="mt-2 text-xs text-slate-500">Uploading…</div>
                    @error('materialFile') <p class="form-error">{{ $message }}</p> @enderror
                @else
                    <label for="materialUrl" class="form-label">Link</label>
                    <input id="materialUrl" type="url" wire:model="materialUrl" placeholder="https://" class="form-input">
                    @error('materialUrl') <p class="form-error">{{ $message }}</p> @enderror
                @endif
            </div>
            <div class="md:col-span-2">
                <label for="materialDescription" class="form-label">Description (optional)</label>
                <textarea id="materialDescription" rows="2" wire:model="materialDescription" class="form-input"></textarea>
            </div>
            <label class="flex items-center gap-2 text-sm md:col-span-2">
                <input type="checkbox" wire:model="materialIsPreparatory" class="size-4 rounded border-slate-300 text-brand-700">
                Attach to the 7-day session reminder email (preparation material)
            </label>
            <div class="md:col-span-2">
                <button type="submit" class="btn-secondary" wire:loading.attr="disabled" wire:target="materialFile,addMaterial">Add material</button>
            </div>
        </form>
    </x-portal.card>
</div>
