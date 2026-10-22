<?php

use App\Actions\Ratings\SubmitRating;
use App\Actions\Sessions\RecordAttendance;
use App\Models\LiveSession;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::portal')] class extends Component
{
    #[Locked]
    public LiveSession $session;

    #[Validate('required|integer|between:1,5')]
    public ?int $score = null;

    #[Validate('nullable|string|max:1000')]
    public string $comment = '';

    public bool $rated = false;

    public function mount(LiveSession $session, RecordAttendance $recordAttendance): void
    {
        $this->authorize('watch', $session);

        $this->session = $session->load('course', 'teacher', 'topic.materials');

        $child = auth()->user()->studentProfile;

        if ($child !== null && $session->isJoinable()) {
            $recordAttendance->handle($child, $session);
        }
    }

    public function render()
    {
        return $this->view()->title($this->session->title);
    }

    public function rate(SubmitRating $submitRating): void
    {
        $this->authorize('rate', $this->session);

        $this->validate();

        $submitRating->handle(auth()->user(), $this->session, $this->score, $this->comment);

        $this->rated = true;
    }
};
?>

@php
    $liveEmbed = $session->isJoinable() ? LiveSession::embedUrlFor($session->stream_url) : null;
    $recordingEmbed = $session->hasEnded() ? LiveSession::embedUrlFor($session->recording_url) : null;
    $embed = $liveEmbed ?? $recordingEmbed;
@endphp

<div class="grid gap-8 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        @if ($embed)
            <div class="aspect-video overflow-hidden rounded-2xl bg-black shadow-lg">
                <iframe src="{{ $embed }}" title="{{ $session->title }}" class="size-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
        @else
            <div class="flex aspect-video flex-col items-center justify-center rounded-3xl bg-brand-700 p-8 text-center text-white">
                @if ($session->isJoinable() && $session->stream_url)
                    <p class="text-lg font-semibold">This lesson is live now</p>
                    <a href="{{ $session->stream_url }}" target="_blank" rel="noopener" class="btn-primary mt-5 px-8 py-3.5 text-base">Join the lesson</a>
                    <p class="mt-3 text-base text-white/80">Opens in a new tab (Microsoft Teams or similar).</p>
                @elseif ($session->hasEnded() && $session->recording_url)
                    <p class="text-lg font-semibold">Recording available</p>
                    <a href="{{ $session->recording_url }}" target="_blank" rel="noopener" class="btn-primary mt-5">Watch recording</a>
                @elseif ($session->hasEnded())
                    <p class="text-lg font-semibold">This session has finished</p>
                    <p class="mt-2 text-base text-white/80">The recording will appear here once it is uploaded.</p>
                @else
                    <p class="text-base text-white/80">Starts</p>
                    <p class="font-display text-3xl font-extrabold">{{ $session->starts_at->format('l j F, g:ia') }}</p>
                    <p class="mt-3 text-base text-white/80">The join button appears here 15 minutes before the start.</p>
                @endif
            </div>
        @endif

        <x-portal.card :title="$session->title">
            <p class="text-sm text-slate-500">{{ $session->course->key_stage->label() }} {{ $session->course->title }} · {{ $session->starts_at->format('j M Y, g:ia') }} · {{ $session->duration_minutes }} minutes @if ($session->teacher) · with {{ $session->teacher->name }} @endif</p>
            @if ($session->description)
                <p class="mt-4 whitespace-pre-line text-slate-700">{{ $session->description }}</p>
            @endif
        </x-portal.card>
    </div>

    <div class="space-y-6">
        @if ($session->topic && $session->topic->materials->isNotEmpty())
            <x-portal.card title="Lesson materials">
                <ul class="space-y-2 text-sm">
                    @foreach ($session->topic->materials as $material)
                        <li><a href="{{ route('materials.download', $material) }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 hover:text-brand-900">{{ $material->title }}</a></li>
                    @endforeach
                </ul>
            </x-portal.card>
        @endif

        @can('rate', $session)
            <x-portal.card title="Rate this lesson">
                @if ($rated)
                    <p class="text-sm text-emerald-700" role="status">Thanks for your feedback!</p>
                @else
                    <form wire:submit="rate" class="space-y-4">
                        <div class="flex gap-1" role="radiogroup" aria-label="Rating">
                            @foreach (range(1, 5) as $star)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="score" value="{{ $star }}" class="sr-only">
                                    <svg @class(['size-8 transition', 'text-accent-500' => $score !== null && $star <= $score, 'text-slate-300 hover:text-accent-300' => $score === null || $star > $score]) viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5Z"/></svg>
                                    <span class="sr-only">{{ $star }} {{ Str::plural('star', $star) }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('score') <p class="form-error">{{ $message }}</p> @enderror
                        <textarea wire:model="comment" rows="3" class="form-input" placeholder="What did you think of the lesson? (optional)"></textarea>
                        <button type="submit" class="btn-secondary w-full">Send rating</button>
                    </form>
                @endif
            </x-portal.card>
        @endcan
    </div>
</div>
