<?php

use App\Models\Child;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Parent dashboard')] class extends Component
{
    /**
     * @return Collection<int, Child>
     */
    #[Computed]
    public function children(): Collection
    {
        $parent = auth()->user()->loadMissing('subscriptions.items');

        return $parent->children()
            ->with(['plan', 'activeCourses', 'student'])
            ->withCount(['quizAttempts as completed_quizzes_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->withAvg(['quizAttempts as average_score' => fn ($query) => $query->whereNotNull('completed_at')], 'percentage')
            ->orderBy('first_name')
            ->get();
    }
};
?>

<div class="space-y-8">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-extrabold text-brand-950">Hello, {{ Str::before(auth()->user()->name, ' ') }}</h2>
            <p class="mt-1 text-slate-600">Manage your children's plans, courses and progress.</p>
        </div>
        <a href="{{ route('parent.children.create') }}" wire:navigate class="btn-primary">Add a child</a>
    </div>

    @if ($this->children->isEmpty())
        <x-portal.card>
            <div class="py-10 text-center">
                <h3 class="text-xl font-bold text-brand-950">Let's get your first child set up</h3>
                <ol class="mx-auto mt-6 grid max-w-3xl gap-4 text-left sm:grid-cols-3">
                    @foreach (['Add your child and create their student login', 'Choose a plan and their courses', 'Start your 7-day free trial'] as $step)
                        <li class="rounded-xl bg-brand-50 p-4 text-sm text-brand-900"><span class="font-bold">{{ $loop->iteration }}.</span> {{ $step }}</li>
                    @endforeach
                </ol>
                <a href="{{ route('parent.children.create') }}" wire:navigate class="btn-secondary mt-8">Add your child</a>
            </div>
        </x-portal.card>
    @else
        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ($this->children as $child)
                @php($subscription = $child->subscription())
                <x-portal.card wire:key="child-{{ $child->id }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-brand-950">{{ $child->full_name }}</h3>
                            <p class="text-sm text-slate-500">Year {{ $child->year_group }} · {{ $child->key_stage->label() }} · login: {{ $child->student?->email }}</p>
                        </div>
                        <x-portal.subscription-status :subscription="$subscription" />
                    </div>

                    <dl class="mt-6 grid grid-cols-3 gap-4 text-center">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <dt class="text-xs text-slate-500">Plan</dt>
                            <dd class="mt-1 font-semibold text-slate-800">{{ $child->plan?->name ?? '—' }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <dt class="text-xs text-slate-500">Quizzes done</dt>
                            <dd class="mt-1 font-semibold text-slate-800">{{ $child->completed_quizzes_count }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <dt class="text-xs text-slate-500">Average score</dt>
                            <dd class="mt-1 font-semibold text-slate-800">{{ $child->average_score !== null ? round($child->average_score).'%' : '—' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6">
                        <p class="text-sm font-semibold text-slate-700">Courses</p>
                        @if ($child->activeCourses->isEmpty())
                            <p class="mt-1 text-sm text-slate-500">No courses selected yet.</p>
                        @else
                            <ul class="mt-2 flex flex-wrap gap-2">
                                @foreach ($child->activeCourses as $course)
                                    <li><x-portal.badge tone="brand">{{ $course->key_stage->label() }} {{ $course->title }}</x-portal.badge></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                        @if ($child->hasActiveSubscription())
                            <a href="{{ route('parent.children.courses', $child) }}" wire:navigate class="btn-ghost py-2 text-brand-700">Change courses</a>
                            <a href="{{ route('parent.billing') }}" wire:navigate class="btn-ghost py-2 text-slate-700">Manage plan</a>
                        @else
                            <a href="{{ route('parent.children.subscribe', $child) }}" wire:navigate class="btn-primary py-2">Choose a plan</a>
                        @endif
                    </div>
                </x-portal.card>
            @endforeach
        </div>
    @endif
</div>
