<?php

use App\Actions\Quizzes\SaveQuizAnswers;
use App\Actions\Quizzes\StartQuizAttempt;
use App\Actions\Quizzes\SubmitQuizAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts::portal')] class extends Component
{
    #[Locked]
    public Quiz $quiz;

    #[Locked]
    public int $attemptId;

    /** @var array<int, int|string|null> question ID => option ID */
    public array $answers = [];

    public function mount(Quiz $quiz, StartQuizAttempt $startAttempt): void
    {
        $this->authorize('attempt', $quiz);

        $this->quiz = $quiz;
        $attempt = $startAttempt->handle(auth()->user()->studentProfile, $quiz);
        $this->attemptId = $attempt->id;

        if ($attempt->isComplete()) {
            session()->flash('status', 'Time ran out on your last try, so we marked the answers you had saved.');
            $this->redirectRoute('student.quizzes.result', [$quiz, $attempt], navigate: true);

            return;
        }

        $this->answers = $attempt->savedAnswers();
    }

    public function render()
    {
        return $this->view()->title($this->quiz->title);
    }

    /**
     * Questions with their options and images; correctness flags are hidden from the client.
     *
     * @return Collection<int, \App\Models\Question>
     */
    #[Computed]
    public function questions(): Collection
    {
        return $this->quiz->questions()->with('options', 'media')->get();
    }

    #[Computed]
    public function attempt(): QuizAttempt
    {
        return QuizAttempt::whereKey($this->attemptId)
            ->whereBelongsTo(auth()->user()->studentProfile)
            ->firstOrFail();
    }

    /**
     * Autosave one answer as soon as it is chosen.
     */
    public function saveAnswer(SaveQuizAnswers $saveAnswers, int $questionId, int $optionId): bool
    {
        return $this->sync($saveAnswers, [$questionId => $optionId]);
    }

    /**
     * Save answers the browser kept while it was offline.
     *
     * @param  array<int|string, int|string|null>  $answers
     */
    public function sync(SaveQuizAnswers $saveAnswers, array $answers): bool
    {
        $this->authorize('attempt', $this->quiz);

        $saveAnswers->handle($this->attempt, array_slice($answers, 0, 500, true));
        $this->answers = array_replace($this->answers, $this->attempt->savedAnswers());

        return true;
    }

    /**
     * @param  array<int|string, int|string|null>  $pending  answers the browser has not saved yet
     */
    public function submit(SaveQuizAnswers $saveAnswers, SubmitQuizAttempt $submitAttempt, array $pending = []): void
    {
        $this->authorize('attempt', $this->quiz);

        $attempt = $this->attempt;

        if ($attempt->isComplete()) {
            $this->redirectRoute('student.quizzes.result', [$this->quiz, $attempt], navigate: true);

            return;
        }

        $saveAnswers->handle($attempt, array_slice(array_replace($this->answers, $pending), 0, 500, true));
        $saved = $attempt->savedAnswers();
        $this->answers = $saved;

        $unanswered = $this->questions->reject(fn ($question) => isset($saved[$question->id]))->count();

        if ($unanswered > 0 && ! $this->quiz->time_limit_minutes) {
            $this->addError('answers', "Answer every question before submitting ({$unanswered} left).");

            return;
        }

        $submitAttempt->handle($attempt, $saved);

        $this->redirectRoute('student.quizzes.result', [$this->quiz, $attempt], navigate: true);
    }
};
?>

@php
    $deadline = $this->attempt->deadline();
    $answered = collect($answers)->filter(fn ($value) => filled($value))->count();
    $total = $this->questions->count();
@endphp

<div
    class="mx-auto max-w-3xl space-y-6"
    x-data="{
        key: 'ma-quiz-{{ $this->attempt->ulid }}',
        pending: {},
        offline: ! navigator.onLine,
        saving: false,
        init() {
            try { this.pending = JSON.parse(localStorage.getItem(this.key) || '{}') } catch (e) { this.pending = {} }
            window.addEventListener('online', () => { this.offline = false; this.saving = false; this.flush() })
            window.addEventListener('offline', () => { this.offline = true })
            setInterval(() => this.flush(), 15000)
            this.flush()
        },
        get hasPending() { return Object.keys(this.pending).length > 0 },
        store() {
            try {
                this.hasPending ? localStorage.setItem(this.key, JSON.stringify(this.pending)) : localStorage.removeItem(this.key)
            } catch (e) {}
        },
        choose(questionId, optionId) {
            this.pending[questionId] = optionId
            this.store()
            this.send($wire.saveAnswer(questionId, optionId), { [questionId]: optionId })
        },
        flush() {
            if (! this.hasPending || this.offline || this.saving) return
            this.send($wire.sync({ ...this.pending }), { ...this.pending })
        },
        async send(request, sent) {
            this.saving = true
            try {
                const timeout = new Promise((resolve) => setTimeout(() => resolve(false), 20000))
                if (await Promise.race([request, timeout])) {
                    for (const id in sent) { if (this.pending[id] === sent[id]) delete this.pending[id] }
                    this.store()
                }
            } catch (e) {
            } finally {
                this.saving = false
            }
        },
        finish() {
            const pending = { ...this.pending }
            $wire.submit(pending).then(() => { this.pending = {}; this.store() })
        },
    }"
>
    <div class="flex flex-col justify-between gap-4 rounded-3xl bg-brand-700 p-6 text-white sm:flex-row sm:items-center sm:p-8">
        <div>
            <p class="text-base text-white/80">{{ $quiz->topic->title }}</p>
            <h2 class="text-2xl font-extrabold">{{ $quiz->title }}</h2>
            <p class="mt-1 text-base text-white/80">{{ $total }} questions · you need {{ $quiz->pass_mark }}% to pass</p>
        </div>
        @if ($deadline)
            <div
                x-data="{ remaining: Math.max(0, Math.floor(({{ $deadline->getTimestamp() }} * 1000 - Date.now()) / 1000)) }"
                x-init="const timer = setInterval(() => { remaining = Math.max(0, remaining - 1); if (remaining === 0) { clearInterval(timer); finish() } }, 1000)"
                class="rounded-xl bg-white/10 px-4 py-2 text-center"
                role="timer"
                aria-live="off"
            >
                <p class="text-sm text-white/80">Time left</p>
                <p class="font-display text-2xl font-extrabold tabular-nums" x-text="`${Math.floor(remaining / 60)}:${String(remaining % 60).padStart(2, '0')}`"></p>
            </div>
        @endif
    </div>

    <div class="sticky top-20 z-10 rounded-2xl bg-white/95 px-5 py-3 shadow-sm ring-1 ring-slate-200 backdrop-blur">
        <div class="flex flex-wrap items-center justify-between gap-2 text-base font-semibold text-slate-700">
            <span>Answered {{ $answered }} of {{ $total }}</span>
            <span class="text-sm" role="status" aria-live="polite">
                <span x-show="offline" x-cloak class="text-amber-700">📴 Offline: keep going, we'll save when you're back</span>
                <span x-show="! offline && (saving || hasPending)" x-cloak class="text-slate-500">Saving…</span>
                <span x-show="! offline && ! saving && ! hasPending" class="text-emerald-700">✓ Answers saved</span>
            </span>
        </div>
        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-accent-500 transition-all" style="width: {{ $total ? round($answered / $total * 100) : 0 }}%"></div>
        </div>
    </div>

    <form x-on:submit.prevent="finish()" class="space-y-6">
        @foreach ($this->questions as $question)
            <fieldset wire:key="question-{{ $question->id }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <legend class="sr-only">Question {{ $loop->iteration }}</legend>
                <p class="text-sm font-bold tracking-wide text-brand-700 uppercase">Question {{ $loop->iteration }} of {{ $loop->count }}</p>

                @if ($question->isPicture())
                    <x-quiz.question-picture :question="$question" class="mt-4" />
                @endif

                <p class="mt-2 text-xl leading-relaxed font-semibold text-slate-900">{{ $question->prompt }}</p>

                <div class="mt-4 grid gap-3 {{ $question->isPicture() ? 'sm:grid-cols-2' : '' }}">
                    @foreach ($question->options as $option)
                        <label wire:key="option-{{ $option->id }}" class="flex min-h-14 cursor-pointer items-center gap-4 rounded-2xl border-2 border-slate-200 px-5 py-4 transition has-checked:border-brand-600 has-checked:bg-brand-50 hover:border-brand-300">
                            <input
                                type="radio"
                                name="question-{{ $question->id }}"
                                wire:model="answers.{{ $question->id }}"
                                value="{{ $option->id }}"
                                x-on:change="choose({{ $question->id }}, {{ $option->id }})"
                                class="size-5 border-slate-300 text-brand-700 focus:ring-brand-500"
                            >
                            <span class="text-lg text-slate-800">{{ $option->text }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        @error('answers') <p class="form-error text-center" role="alert">{{ $message }}</p> @enderror

        <div class="flex flex-col items-end gap-2">
            <button type="submit" class="btn-primary px-10 py-4 text-lg" x-bind:disabled="offline">
                <span wire:loading.remove wire:target="submit">Submit answers</span>
                <span wire:loading wire:target="submit">Marking…</span>
            </button>
            <p x-show="offline" x-cloak class="text-sm text-amber-700">You can submit once you're back online.</p>
        </div>
    </form>
</div>
