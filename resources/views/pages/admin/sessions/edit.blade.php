<?php

use App\Enums\LiveSessionStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts::portal')] class extends Component
{
    #[Locked]
    public ?int $sessionId = null;

    public ?int $course_id = null;

    public ?int $topic_id = null;

    public ?int $teacher_id = null;

    public string $title = '';

    public string $description = '';

    public string $starts_at = '';

    public int $duration_minutes = 150;

    public string $stream_url = '';

    public string $recording_url = '';

    public string $status = 'scheduled';

    public function mount(?LiveSession $session = null): void
    {
        if ($session?->exists) {
            $this->sessionId = $session->id;
            $this->fill($session->only(['course_id', 'topic_id', 'teacher_id', 'title', 'duration_minutes']));
            $this->description = (string) $session->description;
            $this->stream_url = (string) $session->stream_url;
            $this->recording_url = (string) $session->recording_url;
            $this->starts_at = $session->starts_at->format('Y-m-d\TH:i');
            $this->status = $session->status->value;

            return;
        }

        $this->course_id = request()->integer('course') ?: null;
        $this->starts_at = now()->next(Carbon::SATURDAY)->setTime(10, 0)->format('Y-m-d\TH:i');
    }

    public function render()
    {
        return $this->view()->title($this->session ? 'Edit session' : 'Schedule a live session');
    }

    #[Computed]
    public function session(): ?LiveSession
    {
        return $this->sessionId ? LiveSession::with('attendances.child')->find($this->sessionId) : null;
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
     * @return Collection<int, Topic>
     */
    #[Computed]
    public function topics(): Collection
    {
        if ($this->course_id === null) {
            return new Collection;
        }

        return Topic::query()
            ->whereHas('subject', fn ($query) => $query->where('course_id', $this->course_id))
            ->with('subject')
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            ->orderBy('subjects.position')
            ->orderBy('topics.position')
            ->select('topics.*')
            ->get();
    }

    /**
     * Course tutors first, then every other teacher.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function teachers(): Collection
    {
        $courseTeacherIds = $this->course_id ? Course::find($this->course_id)?->teachers()->pluck('users.id') ?? collect() : collect();

        return User::role(Role::Teacher->value)
            ->orderBy('name')
            ->get()
            ->sortByDesc(fn (User $teacher) => $courseTeacherIds->contains($teacher->id))
            ->values();
    }

    public function updatedCourseId(): void
    {
        $this->topic_id = null;
    }

    public function updatedTopicId(): void
    {
        if ($this->title === '' && $this->topic_id) {
            $topic = $this->topics->firstWhere('id', $this->topic_id);
            $this->title = $topic ? $topic->subject->name.': '.$topic->title : '';
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'topic_id' => ['nullable', 'integer', Rule::in($this->topics->modelKeys())],
            'teacher_id' => ['nullable', 'integer', Rule::in($this->teachers->modelKeys())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'between:15,480'],
            'stream_url' => ['nullable', 'url:https', 'max:2048'],
            'recording_url' => ['nullable', 'url:https', 'max:2048'],
            'status' => ['required', Rule::enum(LiveSessionStatus::class)],
        ];
    }

    public function save(): void
    {
        $this->authorize(Permission::ManageLiveSessions->value);

        $validated = $this->validate();

        $session = $this->session ?? new LiveSession;
        $originalStart = $session->starts_at;

        $session->fill([
            ...$validated,
            'description' => $validated['description'] ?: null,
            'stream_url' => $validated['stream_url'] ?: null,
            'recording_url' => $validated['recording_url'] ?: null,
            'starts_at' => Carbon::parse($validated['starts_at']),
        ]);

        if ($originalStart !== null && $originalStart->format('Y-m-d H:i') !== $session->starts_at->format('Y-m-d H:i')) {
            $session->forceFill(['seven_day_reminder_sent_at' => null, 'two_day_reminder_sent_at' => null]);
        }

        $session->save();

        session()->flash('status', 'Session saved.');

        $this->redirectRoute('admin.sessions.edit', $session, navigate: true);
    }

    public function delete(): void
    {
        $this->authorize(Permission::ManageLiveSessions->value);

        $this->session?->delete();

        session()->flash('status', 'Session deleted.');

        $this->redirectRoute('admin.sessions.index', navigate: true);
    }
};
?>

<div class="grid gap-8 xl:grid-cols-3">
    <form wire:submit="save" class="space-y-6 xl:col-span-2" novalidate>
        <x-portal.card title="Session details">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="course_id" class="form-label">Course</label>
                    <select id="course_id" wire:model.live="course_id" class="form-input">
                        <option value="">Select a course</option>
                        @foreach ($this->courses as $course)
                            <option value="{{ $course->id }}">{{ $course->key_stage->label() }} {{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('course_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="topic_id" class="form-label">Topic (optional)</label>
                    <select id="topic_id" wire:model.live="topic_id" class="form-input" @disabled(! $course_id)>
                        <option value="">No specific topic</option>
                        @foreach ($this->topics as $topic)
                            <option value="{{ $topic->id }}">{{ $topic->subject->name }}: {{ Str::limit($topic->title, 60) }}</option>
                        @endforeach
                    </select>
                    @error('topic_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="title" class="form-label">Title</label>
                    <input id="title" type="text" wire:model="title" class="form-input">
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="starts_at" class="form-label">Starts (UK time)</label>
                    <input id="starts_at" type="datetime-local" wire:model="starts_at" class="form-input">
                    @error('starts_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                    <input id="duration_minutes" type="number" min="15" step="15" wire:model="duration_minutes" class="form-input">
                    @error('duration_minutes') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="teacher_id" class="form-label">Tutor</label>
                    <select id="teacher_id" wire:model="teacher_id" class="form-input">
                        <option value="">Unassigned</option>
                        @foreach ($this->teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    @error('teacher_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="status" class="form-label">Status</label>
                    <select id="status" wire:model="status" class="form-input">
                        @foreach (\App\Enums\LiveSessionStatus::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="stream_url" class="form-label">Stream link <span class="font-normal text-slate-500">(YouTube embeds in the portal; Teams and others open in a new tab)</span></label>
                    <input id="stream_url" type="url" wire:model="stream_url" placeholder="https://" class="form-input">
                    @error('stream_url') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="recording_url" class="form-label">Recording link</label>
                    <input id="recording_url" type="url" wire:model="recording_url" placeholder="https://" class="form-input">
                    @error('recording_url') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" rows="4" wire:model="description" class="form-input"></textarea>
                </div>
            </div>
        </x-portal.card>

        <div class="flex items-center justify-between">
            @if ($sessionId)
                <button type="button" wire:click="delete" wire:confirm="Delete this session? Attendance records will be removed too." class="text-sm font-semibold text-red-600 hover:text-red-800">Delete session</button>
            @else
                <span></span>
            @endif
            <div class="flex gap-3">
                <a href="{{ route('admin.sessions.index') }}" wire:navigate class="btn-ghost text-slate-700">Back</a>
                <button type="submit" class="btn-primary">Save session</button>
            </div>
        </div>
    </form>

    @if ($this->session)
        <div class="space-y-6">
            <x-portal.card title="Reminder emails">
                <ul class="space-y-2 text-sm">
                    <li class="flex justify-between"><span>7 days before</span><span class="text-slate-600">{{ $this->session->seven_day_reminder_sent_at?->format('j M, H:i') ?? 'Not sent' }}</span></li>
                    <li class="flex justify-between"><span>2 days before</span><span class="text-slate-600">{{ $this->session->two_day_reminder_sent_at?->format('j M, H:i') ?? 'Not sent' }}</span></li>
                </ul>
                <p class="mt-3 text-xs text-slate-500">Changing the start time resets both reminders.</p>
            </x-portal.card>

            <x-portal.card :title="'Attendance ('.$this->session->attendances->count().')'">
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($this->session->attendances as $attendance)
                        <li class="flex justify-between py-2"><span class="font-medium text-slate-800">{{ $attendance->child->full_name }}</span><span class="text-slate-500">{{ $attendance->joined_at->format('H:i') }}</span></li>
                    @empty
                        <li class="py-2 text-slate-500">No students have joined yet.</li>
                    @endforelse
                </ul>
            </x-portal.card>
        </div>
    @endif
</div>
