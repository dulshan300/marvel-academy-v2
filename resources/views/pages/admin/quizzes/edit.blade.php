<?php

use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
    public Quiz $quiz;

    public string $title = '';

    public string $description = '';

    public int $pass_mark = 60;

    public ?int $time_limit_minutes = null;

    public bool $is_published = false;

    /** Question being edited: null for none, 0 for a new question. */
    public ?int $editingQuestionId = null;

    public string $questionType = 'text';

    /** Explains the picture on picture questions. */
    public string $questionDescription = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $image = null;

    /** Whether the question being edited already has a stored image. */
    public bool $hasStoredImage = false;

    public string $prompt = '';

    public string $explanation = '';

    public int $marks = 1;

    /** @var array<int, array{id: ?int, text: string}> */
    public array $options = [];

    public ?int $correctIndex = null;

    public function mount(Quiz $quiz): void
    {
        $this->authorize('manage', Course::class);

        $this->quiz = $quiz->load('topic.subject.course');
        $this->fill($quiz->only(['title', 'pass_mark', 'time_limit_minutes', 'is_published']));
        $this->description = (string) $quiz->description;
    }

    public function render()
    {
        return $this->view()->title('Quiz: '.$this->quiz->title);
    }

    /**
     * @return Collection<int, Question>
     */
    #[Computed]
    public function questions(): Collection
    {
        return $this->quiz->questions()->with('options', 'media')->get();
    }

    public function saveQuiz(): void
    {
        $this->authorize('manage', Course::class);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'pass_mark' => ['required', 'integer', 'between:1,100'],
            'time_limit_minutes' => ['nullable', 'integer', 'between:1,240'],
            'is_published' => ['boolean'],
        ]);

        if ($validated['is_published'] && ($problem = $this->publishingProblem()) !== null) {
            $this->addError('is_published', $problem);

            return;
        }

        $this->quiz->update($validated);

        session()->flash('status', 'Quiz saved.');
    }

    public function newQuestion(): void
    {
        $this->resetQuestionForm();
        $this->editingQuestionId = 0;
        $this->options = array_fill(0, 4, ['id' => null, 'text' => '']);
    }

    public function editQuestion(int $questionId): void
    {
        $question = $this->questions->firstWhere('id', $questionId);
        abort_if($question === null, 404);

        $this->resetQuestionForm();
        $this->editingQuestionId = $question->id;
        $this->questionType = $question->type->value;
        $this->questionDescription = (string) $question->description;
        $this->hasStoredImage = $question->getFirstMedia('image') !== null;
        $this->prompt = $question->prompt;
        $this->explanation = (string) $question->explanation;
        $this->marks = $question->marks;
        $this->options = $question->options->map(fn ($option) => ['id' => $option->id, 'text' => $option->text])->all();
        $this->correctIndex = $question->options->search(fn ($option) => $option->is_correct);
    }

    public function addOption(): void
    {
        if (count($this->options) < 6) {
            $this->options[] = ['id' => null, 'text' => ''];
        }
    }

    public function removeOption(int $index): void
    {
        if (count($this->options) <= 2) {
            return;
        }

        unset($this->options[$index]);
        $this->options = array_values($this->options);

        if ($this->correctIndex === $index) {
            $this->correctIndex = null;
        } elseif ($this->correctIndex !== null && $this->correctIndex > $index) {
            $this->correctIndex--;
        }
    }

    public function saveQuestion(): void
    {
        $this->authorize('manage', Course::class);

        $isPicture = $this->questionType === QuestionType::Picture->value;

        $this->validate([
            'questionType' => ['required', Rule::enum(QuestionType::class)],
            'questionDescription' => ['nullable', 'string', 'max:3000'],
            'image' => [
                Rule::requiredIf($isPicture && ! $this->hasStoredImage),
                'nullable',
                'image',
                'mimes:'.implode(',', QuestionType::imageExtensions()),
                'max:5120',
            ],
            'prompt' => ['required', 'string', 'max:2000'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'marks' => ['required', 'integer', 'between:1,10'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*.text' => ['required', 'string', 'max:500', 'distinct'],
            'correctIndex' => ['required', 'integer', 'min:0', 'lt:'.count($this->options)],
        ], [
            'correctIndex.required' => 'Mark the correct answer.',
            'image.required' => 'Upload the picture for this question.',
            'options.*.text.distinct' => 'Each option must be different.',
        ], [
            'options.*.text' => 'option',
            'questionDescription' => 'description',
        ]);

        DB::transaction(function () use ($isPicture): void {
            $question = $this->editingQuestionId
                ? $this->quiz->questions()->findOrFail($this->editingQuestionId)
                : $this->quiz->questions()->make(['position' => (int) $this->quiz->questions()->max('position') + 1]);

            $question->fill([
                'type' => $this->questionType,
                'description' => $isPicture ? ($this->questionDescription ?: null) : null,
                'prompt' => $this->prompt,
                'explanation' => $this->explanation ?: null,
                'marks' => $this->marks,
            ])->save();

            if (! $isPicture) {
                $question->clearMediaCollection('image');
            } elseif ($this->image !== null) {
                $question->addMedia($this->image->getRealPath())
                    ->usingFileName(Str::random(24).'.'.$this->image->guessExtension())
                    ->toMediaCollection('image');
            }

            $keptIds = [];

            foreach ($this->options as $position => $option) {
                $attributes = ['text' => $option['text'], 'is_correct' => $position === $this->correctIndex, 'position' => $position];

                $model = $option['id'] !== null
                    ? $question->options()->findOrFail($option['id'])
                    : $question->options()->make();

                $model->fill($attributes)->save();
                $keptIds[] = $model->id;
            }

            $question->options()->whereNotIn('id', $keptIds)->delete();
        });

        $this->resetQuestionForm();
        unset($this->questions);
    }

    public function cancelQuestion(): void
    {
        $this->resetQuestionForm();
    }

    public function deleteQuestion(int $questionId): void
    {
        $this->authorize('manage', Course::class);

        $this->quiz->questions()->findOrFail($questionId)->delete();
        unset($this->questions);

        if ($this->quiz->is_published && $this->publishingProblem() !== null) {
            $this->quiz->update(['is_published' => false]);
            $this->is_published = false;
        }
    }

    public function moveQuestion(int $questionId, int $direction): void
    {
        $this->authorize('manage', Course::class);

        $ordered = $this->questions->values();
        $index = $ordered->search(fn ($question) => $question->id === $questionId);
        $target = $index + ($direction < 0 ? -1 : 1);

        if ($index === false || $target < 0 || $target >= $ordered->count()) {
            return;
        }

        $items = $ordered->all();
        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];

        DB::transaction(function () use ($items): void {
            foreach ($items as $position => $question) {
                $question->forceFill(['position' => $position])->save();
            }
        });

        unset($this->questions);
    }

    /**
     * Reason the quiz cannot be published yet, or null when it is ready.
     */
    private function publishingProblem(): ?string
    {
        if ($this->questions->isEmpty()) {
            return 'Add at least one question before publishing.';
        }

        $invalid = $this->questions->first(fn (Question $question) => $question->options->count() < 2 || $question->options->where('is_correct', true)->count() !== 1);

        return $invalid ? 'Every question needs at least two options and exactly one correct answer.' : null;
    }

    private function resetQuestionForm(): void
    {
        $this->reset('editingQuestionId', 'questionType', 'questionDescription', 'image', 'hasStoredImage', 'prompt', 'explanation', 'marks', 'options', 'correctIndex');
        $this->resetValidation();
    }
};
?>

<div class="space-y-8">
    <nav class="text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('admin.courses.edit', $quiz->topic->subject->course) }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-900">{{ $quiz->topic->subject->course->key_stage->label() }} {{ $quiz->topic->subject->course->title }}</a>
        <span class="mx-1">/</span>
        <a href="{{ route('admin.topics.edit', $quiz->topic) }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-900">{{ $quiz->topic->title }}</a>
    </nav>

    <x-portal.card title="Quiz settings">
        <form wire:submit="saveQuiz" class="grid gap-4 md:grid-cols-4" novalidate>
            <div class="md:col-span-2">
                <label for="title" class="form-label">Title</label>
                <input id="title" type="text" wire:model="title" class="form-input">
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="pass_mark" class="form-label">Pass mark (%)</label>
                <input id="pass_mark" type="number" min="1" max="100" wire:model="pass_mark" class="form-input">
                @error('pass_mark') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="time_limit_minutes" class="form-label">Time limit (min, optional)</label>
                <input id="time_limit_minutes" type="number" min="1" wire:model="time_limit_minutes" class="form-input">
                @error('time_limit_minutes') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-4">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" rows="2" wire:model="description" class="form-input"></textarea>
            </div>
            <div class="flex items-center justify-between md:col-span-4">
                <div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_published" class="size-4 rounded border-slate-300 text-brand-700"> Published (visible to students)</label>
                    @error('is_published') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-secondary">Save settings</button>
            </div>
        </form>
    </x-portal.card>

    <x-portal.card :title="'Questions ('.$this->questions->count().')'">
        <x-slot:actions>
            @if ($editingQuestionId === null)
                <button type="button" wire:click="newQuestion" class="btn-primary py-2">Add question</button>
            @endif
        </x-slot:actions>

        @if ($editingQuestionId !== null)
            <form wire:submit="saveQuestion" class="mb-8 space-y-4 rounded-xl border-2 border-brand-200 bg-brand-50/40 p-5" novalidate>
                <h3 class="font-semibold text-brand-950">{{ $editingQuestionId ? 'Edit question' : 'New question' }}</h3>
                <fieldset>
                    <legend class="form-label">Question type</legend>
                    <div class="flex flex-wrap gap-3">
                        @foreach (QuestionType::cases() as $type)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm has-checked:border-brand-500 has-checked:bg-brand-50">
                                <input type="radio" wire:model.live="questionType" value="{{ $type->value }}" class="text-brand-700">
                                {{ $type->label() }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                @if ($questionType === QuestionType::Picture->value)
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="image" class="form-label">Picture (graph, diagram, shape…)</label>
                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}" alt="New picture preview" class="mb-2 max-h-48 rounded-lg border border-slate-200 bg-white object-contain">
                            @elseif ($hasStoredImage && $editingQuestionId)
                                <img src="{{ route('questions.image', $editingQuestionId) }}" alt="Current picture" class="mb-2 max-h-48 rounded-lg border border-slate-200 bg-white object-contain">
                            @endif
                            <input id="image" type="file" wire:model="image" accept="{{ collect(QuestionType::imageExtensions())->map(fn ($extension) => '.'.$extension)->join(',') }}" class="form-input py-2 text-sm">
                            <p class="mt-1 text-xs text-slate-500">JPG, PNG, WebP or GIF up to 5 MB.{{ $hasStoredImage ? ' Leave empty to keep the current picture.' : '' }}</p>
                            <div wire:loading wire:target="image" class="mt-1 text-xs text-slate-500">Uploading…</div>
                            @error('image') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="questionDescription" class="form-label">Description of the picture</label>
                            <textarea id="questionDescription" rows="6" wire:model="questionDescription" class="form-input" placeholder="e.g. The graph shows the distance a cyclist travels over 5 hours."></textarea>
                            @error('questionDescription') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif
                <div>
                    <label for="prompt" class="form-label">Question</label>
                    <textarea id="prompt" rows="2" wire:model="prompt" class="form-input"></textarea>
                    @error('prompt') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <fieldset>
                    <legend class="form-label">Options <span class="font-normal text-slate-500">(select the correct answer)</span></legend>
                    <div class="space-y-2">
                        @foreach ($options as $index => $option)
                            <div class="flex items-center gap-3" wire:key="option-row-{{ $index }}">
                                <input type="radio" wire:model="correctIndex" value="{{ $index }}" class="size-4 text-emerald-600" aria-label="Correct answer {{ $index + 1 }}">
                                <input type="text" wire:model="options.{{ $index }}.text" class="form-input py-2" placeholder="Option {{ $index + 1 }}">
                                <button type="button" wire:click="removeOption({{ $index }})" class="text-slate-400 hover:text-red-600" @disabled(count($options) <= 2) title="Remove option">✕</button>
                            </div>
                            @error("options.{$index}.text") <p class="form-error ml-7">{{ $message }}</p> @enderror
                        @endforeach
                    </div>
                    @error('correctIndex') <p class="form-error">{{ $message }}</p> @enderror
                    @if (count($options) < 6)
                        <button type="button" wire:click="addOption" class="mt-2 text-sm font-semibold text-brand-700">+ Add option</button>
                    @endif
                </fieldset>
                <div class="grid gap-4 sm:grid-cols-4">
                    <div class="sm:col-span-3">
                        <label for="explanation" class="form-label">Explanation (shown after marking)</label>
                        <textarea id="explanation" rows="2" wire:model="explanation" class="form-input"></textarea>
                    </div>
                    <div>
                        <label for="marks" class="form-label">Marks</label>
                        <input id="marks" type="number" min="1" max="10" wire:model="marks" class="form-input">
                        @error('marks') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="cancelQuestion" class="btn-ghost text-slate-700">Cancel</button>
                    <button type="submit" class="btn-primary">Save question</button>
                </div>
            </form>
        @endif

        <ol class="space-y-3">
            @forelse ($this->questions as $question)
                <li wire:key="question-{{ $question->id }}" class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 gap-3">
                            @if ($question->isPicture() && $question->getFirstMedia('image'))
                                <img src="{{ route('questions.image', $question) }}" alt="" loading="lazy" class="size-16 shrink-0 rounded-lg border border-slate-200 bg-white object-contain">
                            @endif
                            <div class="min-w-0">
                                <p class="font-medium text-slate-800">{{ $loop->iteration }}. {{ $question->prompt }} <span class="text-xs text-slate-400">({{ $question->marks }} {{ Str::plural('mark', $question->marks) }})</span></p>
                                @if ($question->isPicture())
                                    <x-portal.badge tone="brand" class="mt-1">Picture question</x-portal.badge>
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2 text-sm">
                            <button type="button" wire:click="moveQuestion({{ $question->id }}, -1)" class="text-slate-400 hover:text-slate-700" title="Move up">↑</button>
                            <button type="button" wire:click="moveQuestion({{ $question->id }}, 1)" class="text-slate-400 hover:text-slate-700" title="Move down">↓</button>
                            <button type="button" wire:click="editQuestion({{ $question->id }})" class="font-semibold text-brand-700">Edit</button>
                            <button type="button" wire:click="deleteQuestion({{ $question->id }})" wire:confirm="Delete this question?" class="text-red-600">Delete</button>
                        </div>
                    </div>
                    <ul class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                        @foreach ($question->options as $option)
                            <li @class(['rounded-md px-2 py-1', 'bg-emerald-50 font-semibold text-emerald-800' => $option->is_correct, 'text-slate-600' => ! $option->is_correct])>{{ $option->text }}</li>
                        @endforeach
                    </ul>
                </li>
            @empty
                <li class="text-sm text-slate-500">No questions yet.</li>
            @endforelse
        </ol>
    </x-portal.card>
</div>
