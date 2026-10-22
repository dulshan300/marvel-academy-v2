<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Child;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts::portal')] class extends Component
{
    #[Locked]
    public User $parent;

    public function mount(User $parent): void
    {
        abort_unless($parent->hasRole(Role::Parent->value), 404);

        $this->parent = $parent->load('subscriptions');
    }

    public function render()
    {
        return $this->view()->title($this->parent->name);
    }

    /**
     * @return Collection<int, Child>
     */
    #[Computed]
    public function children(): Collection
    {
        return $this->parent->children()
            ->with(['plan', 'student', 'activeCourses'])
            ->withAvg(['quizAttempts as average_score' => fn ($query) => $query->whereNotNull('completed_at')], 'percentage')
            ->withCount([
                'quizAttempts as quizzes_taken' => fn ($query) => $query->whereNotNull('completed_at'),
                'attendances',
            ])
            ->get();
    }

    /**
     * @return Collection<int, \App\Models\QuizAttempt>
     */
    #[Computed]
    public function recentAttempts(): Collection
    {
        return \App\Models\QuizAttempt::whereIn('child_id', $this->children->modelKeys())
            ->whereNotNull('completed_at')
            ->with('quiz', 'child')
            ->latest('completed_at')
            ->limit(10)
            ->get();
    }

    public function toggleActive(?int $userId = null): void
    {
        $this->authorize(Permission::ManageFamilies->value);

        $user = $userId === null
            ? $this->parent
            : User::whereIn('id', $this->children->pluck('student_id')->filter())->findOrFail($userId);

        $user->update(['is_active' => ! $user->is_active]);

        $this->parent->refresh();
        unset($this->children);
    }
};
?>

<div class="space-y-8">
    <x-portal.card title="Parent account">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">Email</dt><dd class="font-semibold text-slate-800">{{ $parent->email }}</dd></div>
                <div><dt class="text-slate-500">Phone</dt><dd class="font-semibold text-slate-800">{{ $parent->phone ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Last login</dt><dd class="font-semibold text-slate-800">{{ $parent->last_login_at?->diffForHumans() ?? 'Never' }}</dd></div>
            </dl>
            <button type="button" wire:click="toggleActive" wire:confirm="{{ $parent->is_active ? 'Deactivate this parent? They will be signed out.' : 'Reactivate this parent?' }}" @class(['btn py-2', 'bg-red-50 text-red-700 hover:bg-red-100' => $parent->is_active, 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => ! $parent->is_active])>
                {{ $parent->is_active ? 'Deactivate account' : 'Reactivate account' }}
            </button>
        </div>
    </x-portal.card>

    <div class="grid gap-6 xl:grid-cols-2">
        @forelse ($this->children as $child)
            <x-portal.card :title="$child->full_name" wire:key="child-{{ $child->id }}">
                <x-slot:actions>
                    <x-portal.subscription-status :subscription="$parent->subscription($child->subscriptionType())" />
                </x-slot:actions>
                <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div><dt class="text-slate-500">Year</dt><dd class="font-semibold">{{ $child->year_group }} ({{ $child->key_stage->label() }})</dd></div>
                    <div><dt class="text-slate-500">Plan</dt><dd class="font-semibold">{{ $child->plan?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Quiz average</dt><dd class="font-semibold">{{ $child->average_score !== null ? round($child->average_score).'%' : '—' }} <span class="font-normal text-slate-500">({{ $child->quizzes_taken }})</span></dd></div>
                    <div><dt class="text-slate-500">Sessions joined</dt><dd class="font-semibold">{{ $child->attendances_count }}</dd></div>
                </dl>
                <p class="mt-4 text-sm text-slate-600"><span class="font-semibold text-slate-800">Courses:</span> {{ $child->activeCourses->map(fn ($course) => $course->key_stage->label().' '.$course->title)->join(', ') ?: 'None' }}</p>
                @if ($child->student)
                    <div class="mt-4 flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm">
                        <span>Student login: <span class="font-semibold">{{ $child->student->email }}</span> @unless ($child->student->is_active) <x-portal.badge tone="red">Deactivated</x-portal.badge> @endunless</span>
                        <button type="button" wire:click="toggleActive({{ $child->student->id }})" class="font-semibold text-brand-700">{{ $child->student->is_active ? 'Deactivate' : 'Reactivate' }}</button>
                    </div>
                @endif
            </x-portal.card>
        @empty
            <x-portal.card><p class="text-sm text-slate-500">No children registered yet.</p></x-portal.card>
        @endforelse
    </div>

    <x-portal.card title="Recent quiz results">
        <table class="w-full text-left text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->recentAttempts as $attempt)
                    <tr wire:key="attempt-{{ $attempt->id }}">
                        <td class="py-2 font-medium text-slate-800">{{ $attempt->child->first_name }}</td>
                        <td class="py-2 text-slate-600">{{ $attempt->quiz->title }}</td>
                        <td class="py-2"><x-portal.badge :tone="$attempt->passed ? 'green' : 'amber'">{{ $attempt->percentage }}%</x-portal.badge></td>
                        <td class="py-2 text-right text-slate-500">{{ $attempt->completed_at->format('j M Y') }}</td>
                    </tr>
                @empty
                    <tr><td class="py-2 text-slate-500">No quizzes completed yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-portal.card>
</div>
