<?php

use App\Enums\KeyStage;
use App\Enums\Role;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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
    public ?int $courseId = null;

    public string $title = '';

    public string $key_stage = 'ks3';

    public string $subject_area = '';

    public string $summary = '';

    public string $description = '';

    public string $price_per_hour = '4.00';

    public string $price_monthly = '19.99';

    public int $hours_per_month = 5;

    public bool $is_featured = false;

    public bool $is_published = false;

    /** @var array<int, int> */
    public array $teacherIds = [];

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $cover = null;

    public string $newSubjectName = '';

    public ?int $newSubjectYear = null;

    /** @var array<int, string> New topic titles keyed by subject ID. */
    public array $newTopicTitles = [];

    public function mount(?Course $course = null): void
    {
        if ($course?->exists) {
            $this->courseId = $course->id;
            $this->fill($course->only(['title', 'subject_area', 'summary', 'description', 'hours_per_month', 'is_featured', 'is_published']));
            $this->description = (string) $course->description;
            $this->key_stage = $course->key_stage->value;
            $this->price_per_hour = number_format($course->price_per_hour / 100, 2, '.', '');
            $this->price_monthly = number_format($course->price_monthly / 100, 2, '.', '');
            $this->teacherIds = $course->teachers()->pluck('users.id')->all();
        }
    }

    public function render()
    {
        return $this->view()->title($this->course ? 'Edit '.$this->course->key_stage->label().' '.$this->course->title : 'New course');
    }

    #[Computed]
    public function course(): ?Course
    {
        return $this->courseId ? Course::with('media')->find($this->courseId) : null;
    }

    /**
     * @return Collection<int, Subject>
     */
    #[Computed]
    public function subjects(): Collection
    {
        return $this->courseId
            ? Subject::where('course_id', $this->courseId)->with(['topics' => fn ($query) => $query->withCount(['materials', 'quizzes'])])->orderBy('position')->get()
            : new Collection;
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function teachers(): Collection
    {
        return User::role(Role::Teacher->value)->orderBy('name')->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'key_stage' => ['required', Rule::enum(KeyStage::class)],
            'subject_area' => ['required', 'string', 'max:64'],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price_per_hour' => ['required', 'numeric', 'min:0', 'max:1000', 'decimal:0,2'],
            'price_monthly' => ['required', 'numeric', 'min:0', 'max:5000', 'decimal:0,2'],
            'hours_per_month' => ['required', 'integer', 'min:1', 'max:200'],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
            'teacherIds' => ['array'],
            'teacherIds.*' => ['integer', Rule::in($this->teachers->modelKeys())],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function save(): void
    {
        $this->authorize('manage', Course::class);

        $validated = $this->validate();

        $attributes = [
            ...collect($validated)->only(['title', 'key_stage', 'subject_area', 'summary', 'description', 'hours_per_month', 'is_featured', 'is_published'])->all(),
            'price_per_hour' => (int) round($validated['price_per_hour'] * 100),
            'price_monthly' => (int) round($validated['price_monthly'] * 100),
        ];

        $course = DB::transaction(function () use ($attributes, $validated): Course {
            $course = $this->course ?? new Course;
            $course->fill($attributes)->save();
            $course->teachers()->sync(array_map('intval', $validated['teacherIds']));

            return $course;
        });

        if ($this->cover !== null) {
            $course->addMedia($this->cover->getRealPath())
                ->usingFileName(str($course->slug)->append('.', $this->cover->getClientOriginalExtension())->toString())
                ->toMediaCollection('cover');
            $this->cover = null;
        }

        session()->flash('status', 'Course saved.');

        $this->redirectRoute('admin.courses.edit', $course, navigate: true);
    }

    public function addSubject(): void
    {
        $this->authorize('manage', Course::class);
        $this->validate([
            'newSubjectName' => ['required', 'string', 'max:120'],
            'newSubjectYear' => ['nullable', 'integer', Rule::in($this->course->key_stage->years())],
        ], attributes: ['newSubjectYear' => 'school year']);

        $this->course->subjects()->create([
            'name' => $this->newSubjectName,
            'year_group' => $this->newSubjectYear,
            'position' => (int) $this->subjects->max('position') + 1,
        ]);

        $this->reset('newSubjectName', 'newSubjectYear');
        unset($this->subjects);
    }

    public function renameSubject(int $subjectId, string $name): void
    {
        $this->authorize('manage', Course::class);

        if (trim($name) === '' || mb_strlen($name) > 120) {
            return;
        }

        $this->findSubject($subjectId)->update(['name' => trim($name)]);
        unset($this->subjects);
    }

    /**
     * Set which school year sees the subject; blank means every year in the key stage.
     */
    public function setSubjectYear(int $subjectId, string $year): void
    {
        $this->authorize('manage', Course::class);

        $yearGroup = $year === '' ? null : (int) $year;

        if ($yearGroup !== null && ! in_array($yearGroup, $this->course->key_stage->years(), true)) {
            return;
        }

        $this->findSubject($subjectId)->update(['year_group' => $yearGroup]);
        unset($this->subjects);
    }

    public function moveSubject(int $subjectId, int $direction): void
    {
        $this->authorize('manage', Course::class);

        $this->reorder($this->subjects, $subjectId, $direction);
        unset($this->subjects);
    }

    public function deleteSubject(int $subjectId): void
    {
        $this->authorize('manage', Course::class);

        $subject = $this->findSubject($subjectId);
        $subject->topics->each(fn (Topic $topic) => $this->deleteMaterials($topic));
        $subject->delete();
        unset($this->subjects);
    }

    public function addTopic(int $subjectId): void
    {
        $this->authorize('manage', Course::class);
        $this->validate(["newTopicTitles.{$subjectId}" => ['required', 'string', 'max:255']], attributes: ["newTopicTitles.{$subjectId}" => 'topic title']);

        $subject = $this->findSubject($subjectId);
        $subject->topics()->create([
            'title' => $this->newTopicTitles[$subjectId],
            'position' => (int) $subject->topics()->max('position') + 1,
        ]);

        unset($this->newTopicTitles[$subjectId], $this->subjects);
    }

    public function moveTopic(int $topicId, int $direction): void
    {
        $this->authorize('manage', Course::class);

        $topic = $this->findTopic($topicId);
        $this->reorder($topic->subject->topics()->orderBy('position')->get(), $topicId, $direction);
        unset($this->subjects);
    }

    public function deleteTopic(int $topicId): void
    {
        $this->authorize('manage', Course::class);

        $topic = $this->findTopic($topicId);
        $this->deleteMaterials($topic);
        $topic->delete();
        unset($this->subjects);
    }

    /**
     * Delete materials through Eloquent so Media Library removes their files.
     */
    private function deleteMaterials(Topic $topic): void
    {
        $topic->materials()->get()->each->delete();
    }

    private function findSubject(int $subjectId): Subject
    {
        return Subject::where('course_id', $this->courseId)->findOrFail($subjectId);
    }

    private function findTopic(int $topicId): Topic
    {
        return Topic::whereHas('subject', fn ($query) => $query->where('course_id', $this->courseId))->findOrFail($topicId);
    }

    /**
     * Swap an item with its neighbour and renumber positions sequentially.
     *
     * @param  \Illuminate\Support\Collection<int, Subject|Topic>  $items
     */
    private function reorder($items, int $id, int $direction): void
    {
        $ordered = $items->values()->all();
        $index = collect($ordered)->search(fn ($item) => $item->id === $id);
        $target = $index + ($direction < 0 ? -1 : 1);

        if ($index === false || ! isset($ordered[$target])) {
            return;
        }

        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        DB::transaction(function () use ($ordered): void {
            foreach ($ordered as $position => $item) {
                $item->forceFill(['position' => $position])->save();
            }
        });
    }
};
?>

<div class="grid gap-8 2xl:grid-cols-5">
    <form wire:submit="save" class="space-y-6 2xl:col-span-3" novalidate>
        <x-portal.card title="Course details">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="title" class="form-label">Title</label>
                    <input id="title" type="text" wire:model="title" class="form-input">
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="key_stage" class="form-label">Key stage</label>
                    <select id="key_stage" wire:model="key_stage" class="form-input">
                        @foreach (\App\Enums\KeyStage::cases() as $keyStage)
                            <option value="{{ $keyStage->value }}">{{ $keyStage->label() }} ({{ $keyStage->yearRange() }})</option>
                        @endforeach
                    </select>
                    @error('key_stage') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="subject_area" class="form-label">Subject area</label>
                    <input id="subject_area" type="text" wire:model="subject_area" list="subject-areas" class="form-input">
                    <datalist id="subject-areas">
                        @foreach (['Mathematics', 'Biology', 'Chemistry', 'Physics'] as $area)
                            <option value="{{ $area }}"></option>
                        @endforeach
                    </datalist>
                    @error('subject_area') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="summary" class="form-label">Summary <span class="font-normal text-slate-500">(shown on course cards)</span></label>
                    <textarea id="summary" rows="2" wire:model="summary" class="form-input"></textarea>
                    @error('summary') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" rows="6" wire:model="description" class="form-input"></textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="price_per_hour" class="form-label">Price per hour (£)</label>
                    <input id="price_per_hour" type="number" step="0.01" min="0" wire:model="price_per_hour" class="form-input">
                    @error('price_per_hour') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="price_monthly" class="form-label">Price per month (£)</label>
                    <input id="price_monthly" type="number" step="0.01" min="0" wire:model="price_monthly" class="form-input">
                    @error('price_monthly') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="hours_per_month" class="form-label">Live hours per month</label>
                    <input id="hours_per_month" type="number" min="1" wire:model="hours_per_month" class="form-input">
                    @error('hours_per_month') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col justify-end gap-3 pb-1">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_published" class="size-4 rounded border-slate-300 text-brand-700"> Published</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_featured" class="size-4 rounded border-slate-300 text-brand-700"> Featured on home page</label>
                </div>
            </div>
        </x-portal.card>

        <x-portal.card title="Cover image & tutors">
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="cover" class="form-label">Cover image</label>
                    @if ($cover)
                        <img src="{{ $cover->temporaryUrl() }}" alt="" class="mb-3 aspect-16/10 w-full rounded-xl object-cover">
                    @elseif ($this->course?->getFirstMediaUrl('cover'))
                        <img src="{{ $this->course->getFirstMediaUrl('cover') }}" alt="" class="mb-3 aspect-16/10 w-full rounded-xl object-cover">
                    @endif
                    <input id="cover" type="file" wire:model="cover" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-semibold file:text-brand-700">
                    <div wire:loading wire:target="cover" class="mt-2 text-xs text-slate-500">Uploading…</div>
                    @error('cover') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <fieldset>
                    <legend class="form-label">Tutors</legend>
                    <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3">
                        @forelse ($this->teachers as $teacher)
                            <label class="flex items-center gap-2 text-sm" wire:key="teacher-{{ $teacher->id }}">
                                <input type="checkbox" wire:model="teacherIds" value="{{ $teacher->id }}" class="size-4 rounded border-slate-300 text-brand-700">
                                {{ $teacher->name }}
                            </label>
                        @empty
                            <p class="text-sm text-slate-500">No teachers yet.</p>
                        @endforelse
                    </div>
                    @error('teacherIds.*') <p class="form-error">{{ $message }}</p> @enderror
                </fieldset>
            </div>
        </x-portal.card>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.courses.index') }}" wire:navigate class="btn-ghost text-slate-700">Back to courses</a>
            <button type="submit" class="btn-primary">Save course</button>
        </div>
    </form>

    <div class="2xl:col-span-2">
        <x-portal.card title="Subjects & topics">
            @if (! $courseId)
                <p class="text-sm text-slate-500">Save the course first, then add its subjects (for example year groups) and monthly topics.</p>
            @else
                <div class="space-y-5">
                    @foreach ($this->subjects as $subject)
                        <div wire:key="subject-{{ $subject->id }}" class="rounded-xl border border-slate-200">
                            <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-3 py-2">
                                <input type="text" value="{{ $subject->name }}" wire:change="renameSubject({{ $subject->id }}, $event.target.value)" class="min-w-0 flex-1 rounded-md border-transparent bg-transparent px-1 font-semibold text-slate-800 focus:border-brand-400 focus:bg-white" aria-label="Subject name">
                                <select wire:change="setSubjectYear({{ $subject->id }}, $event.target.value)" class="rounded-md border-slate-200 bg-white py-1 pr-8 pl-2 text-sm text-slate-700" aria-label="School year for {{ $subject->name }}">
                                    <option value="" @selected($subject->year_group === null)>All years</option>
                                    @foreach ($this->course->key_stage->years() as $year)
                                        <option value="{{ $year }}" @selected($subject->year_group === $year)>Year {{ $year }}</option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="moveSubject({{ $subject->id }}, -1)" class="rounded p-1 text-slate-500 hover:bg-white" title="Move up">↑</button>
                                <button type="button" wire:click="moveSubject({{ $subject->id }}, 1)" class="rounded p-1 text-slate-500 hover:bg-white" title="Move down">↓</button>
                                <button type="button" wire:click="deleteSubject({{ $subject->id }})" wire:confirm="Delete {{ $subject->name }} and all its topics, materials and quizzes?" class="rounded p-1 text-red-500 hover:bg-white" title="Delete">✕</button>
                            </div>
                            <ul class="divide-y divide-slate-100">
                                @foreach ($subject->topics as $topic)
                                    <li wire:key="topic-{{ $topic->id }}" class="flex items-center gap-2 px-3 py-2 text-sm">
                                        <a href="{{ route('admin.topics.edit', $topic) }}" wire:navigate class="min-w-0 flex-1 truncate font-medium text-brand-700 hover:text-brand-900">{{ $topic->title }}</a>
                                        <span class="shrink-0 text-xs text-slate-400">{{ $topic->materials_count }}m · {{ $topic->quizzes_count }}q</span>
                                        <button type="button" wire:click="moveTopic({{ $topic->id }}, -1)" class="text-slate-400 hover:text-slate-700" title="Move up">↑</button>
                                        <button type="button" wire:click="moveTopic({{ $topic->id }}, 1)" class="text-slate-400 hover:text-slate-700" title="Move down">↓</button>
                                        <button type="button" wire:click="deleteTopic({{ $topic->id }})" wire:confirm="Delete this topic with its materials and quizzes?" class="text-red-400 hover:text-red-600" title="Delete">✕</button>
                                    </li>
                                @endforeach
                            </ul>
                            <form wire:submit="addTopic({{ $subject->id }})" class="flex gap-2 border-t border-slate-100 p-2">
                                <input type="text" wire:model="newTopicTitles.{{ $subject->id }}" placeholder="New topic title" class="form-input py-2 text-sm">
                                <button type="submit" class="btn-ghost shrink-0 py-2 text-brand-700">Add</button>
                            </form>
                            @error("newTopicTitles.{$subject->id}") <p class="form-error px-3 pb-2">{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    <form wire:submit="addSubject" class="flex gap-2">
                        <input type="text" wire:model="newSubjectName" placeholder="New subject, e.g. Year 7" class="form-input">
                        <select wire:model="newSubjectYear" class="form-input w-auto shrink-0" aria-label="School year">
                            <option value="">All years</option>
                            @foreach ($this->course->key_stage->years() as $year)
                                <option value="{{ $year }}">Year {{ $year }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-secondary shrink-0">Add subject</button>
                    </form>
                    @error('newSubjectName') <p class="form-error">{{ $message }}</p> @enderror
                    @error('newSubjectYear') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="text-xs text-slate-500">Students only see subjects for their own school year, plus any set to “All years”.</p>
                </div>
            @endif
        </x-portal.card>
    </div>
</div>
