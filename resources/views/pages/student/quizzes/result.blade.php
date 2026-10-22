<?php

use App\Models\Course;
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
    public QuizAttempt $attempt;

    public function mount(Quiz $quiz, QuizAttempt $attempt): void
    {
        $this->authorize('viewAttempt', [$quiz, $attempt]);

        $this->quiz = $quiz;
        $this->attempt = $attempt->load('answers');
    }

    public function render()
    {
        return $this->view()->title('Results: '.$this->quiz->title);
    }

    /**
     * @return Collection<int, \App\Models\Question>
     */
    #[Computed]
    public function questions(): Collection
    {
        return $this->quiz->questions()->with('options')->get();
    }

    #[Computed]
    public function course(): Course
    {
        return $this->quiz->topic->subject->course;
    }
};
?>

@php
    $questions = $this->questions;
    $answers = $attempt->answers->keyBy('question_id');
    $course = $this->course;
@endphp

<div class="mx-auto max-w-3xl space-y-6">
    <div @class([
        'rounded-2xl p-8 text-center',
        'bg-emerald-50 ring-1 ring-emerald-200' => $attempt->passed,
        'bg-amber-50 ring-1 ring-amber-200' => ! $attempt->passed,
    ])>
        <p class="text-sm font-semibold text-slate-600">{{ $quiz->title }}</p>
        <p class="mt-2 font-display text-6xl font-black {{ $attempt->passed ? 'text-emerald-700' : 'text-amber-700' }}">{{ $attempt->percentage }}%</p>
        <p class="mt-2 text-slate-700">{{ $attempt->score }} out of {{ $attempt->max_score }} correct · {{ $attempt->passed ? 'Passed — great work!' : 'Pass mark is '.$quiz->pass_mark.'%. Review the explanations and try again.' }}</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            @if (auth()->user()->studentProfile)
                <a href="{{ route('student.quizzes.take', $quiz) }}" wire:navigate class="btn-secondary">Retake quiz</a>
                <a href="{{ route('student.courses.show', $course) }}" wire:navigate class="btn-ghost text-slate-700">Back to course</a>
            @endif
        </div>
    </div>

    @foreach ($questions as $question)
        @php($answer = $answers->get($question->id))
        <div wire:key="review-{{ $question->id }}" class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex items-start justify-between gap-4">
                <p class="font-semibold text-slate-900">{{ $loop->iteration }}. {{ $question->prompt }}</p>
                @if ($answer?->is_correct)
                    <x-portal.badge tone="green">Correct</x-portal.badge>
                @else
                    <x-portal.badge tone="red">{{ $answer?->question_option_id ? 'Incorrect' : 'Not answered' }}</x-portal.badge>
                @endif
            </div>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($question->options as $option)
                    <li @class([
                        'rounded-lg px-4 py-2',
                        'bg-emerald-50 font-semibold text-emerald-800' => $option->is_correct,
                        'bg-red-50 text-red-800 line-through' => ! $option->is_correct && $answer?->question_option_id === $option->id,
                        'text-slate-600' => ! $option->is_correct && $answer?->question_option_id !== $option->id,
                    ])>
                        {{ $option->text }}
                        @if ($answer?->question_option_id === $option->id) <span class="no-underline">(your answer)</span> @endif
                    </li>
                @endforeach
            </ul>
            @if ($question->explanation)
                <p class="mt-4 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-900"><span class="font-semibold">Explanation:</span> {{ $question->explanation }}</p>
            @endif
        </div>
    @endforeach
</div>
