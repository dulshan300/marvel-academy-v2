<?php

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
        $this->attemptId = $startAttempt->handle(auth()->user()->studentProfile, $quiz)->id;
    }

    public function render()
    {
        return $this->view()->title($this->quiz->title);
    }

    /**
     * Questions with their options; correctness flags are hidden from the client.
     *
     * @return Collection<int, \App\Models\Question>
     */
    #[Computed]
    public function questions(): Collection
    {
        return $this->quiz->questions()->with('options')->get();
    }

    #[Computed]
    public function attempt(): QuizAttempt
    {
        return QuizAttempt::whereKey($this->attemptId)
            ->whereBelongsTo(auth()->user()->studentProfile)
            ->firstOrFail();
    }

    public function submit(SubmitQuizAttempt $submitAttempt): void
    {
        $this->authorize('attempt', $this->quiz);

        $unanswered = $this->questions->reject(fn ($question) => filled($this->answers[$question->id] ?? null))->count();

        if ($unanswered > 0 && ! $this->quiz->time_limit_minutes) {
            $this->addError('answers', "Answer every question before submitting ({$unanswered} left).");

            return;
        }

        $attempt = $this->attempt;

        if (! $attempt->isComplete()) {
            $submitAttempt->handle($attempt, $this->answers);
        }

        $this->redirectRoute('student.quizzes.result', [$this->quiz, $attempt], navigate: true);
    }
};
?>

@php
    $deadline = $quiz->time_limit_minutes ? $this->attempt->started_at->copy()->addMinutes($quiz->time_limit_minutes) : null;
@endphp

@php $answered = collect($answers)->filter(fn ($value) => filled($value))->count(); @endphp

<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex flex-col justify-between gap-4 rounded-3xl bg-brand-700 p-6 text-white sm:flex-row sm:items-center sm:p-8">
        <div>
            <p class="text-base text-white/80">{{ $quiz->topic->title }}</p>
            <h2 class="text-2xl font-extrabold">{{ $quiz->title }}</h2>
            <p class="mt-1 text-base text-white/80">{{ $this->questions->count() }} questions · you need {{ $quiz->pass_mark }}% to pass</p>
        </div>
        @if ($deadline)
            <div
                x-data="{ remaining: Math.max(0, Math.floor(({{ $deadline->getTimestamp() }} * 1000 - Date.now()) / 1000)) }"
                x-init="const timer = setInterval(() => { remaining = Math.max(0, remaining - 1); if (remaining === 0) { clearInterval(timer); $wire.submit() } }, 1000)"
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
        <div class="flex items-center justify-between text-base font-semibold text-slate-700">
            <span>Answered {{ $answered }} of {{ $this->questions->count() }}</span>
            @if ($answered === $this->questions->count() && $answered > 0)
                <span class="text-emerald-700">All done, ready to submit! ✅</span>
            @endif
        </div>
        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-accent-500 transition-all" style="width: {{ $this->questions->count() ? round($answered / $this->questions->count() * 100) : 0 }}%"></div>
        </div>
    </div>

    <form wire:submit="submit" class="space-y-6">
        @foreach ($this->questions as $question)
            <fieldset wire:key="question-{{ $question->id }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <legend class="sr-only">Question {{ $loop->iteration }}</legend>
                <p class="text-sm font-bold tracking-wide text-brand-700 uppercase">Question {{ $loop->iteration }} of {{ $loop->count }}</p>
                <p class="mt-2 text-xl leading-relaxed font-semibold text-slate-900">{{ $question->prompt }}</p>

                <div class="mt-4 grid gap-3">
                    @foreach ($question->options as $option)
                        <label wire:key="option-{{ $option->id }}" class="flex min-h-14 cursor-pointer items-center gap-4 rounded-2xl border-2 border-slate-200 px-5 py-4 transition has-checked:border-brand-600 has-checked:bg-brand-50 hover:border-brand-300">
                            <input type="radio" name="question-{{ $question->id }}" wire:model.live="answers.{{ $question->id }}" value="{{ $option->id }}" class="size-5 border-slate-300 text-brand-700 focus:ring-brand-500">
                            <span class="text-lg text-slate-800">{{ $option->text }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        @error('answers') <p class="form-error text-center" role="alert">{{ $message }}</p> @enderror

        <div class="flex justify-end">
            <button type="submit" class="btn-primary px-10 py-4 text-lg">
                <span wire:loading.remove wire:target="submit">Submit answers</span>
                <span wire:loading wire:target="submit">Marking…</span>
            </button>
        </div>
    </form>
</div>
