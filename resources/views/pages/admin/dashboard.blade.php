<?php

use App\Enums\Role;
use App\Models\Child;
use App\Models\ContactMessage;
use App\Models\LiveSession;
use App\Models\Plan;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Cashier\Subscription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Admin dashboard')] class extends Component
{
    /**
     * @return array<string, int|string>
     */
    #[Computed]
    public function stats(): array
    {
        $validSubscriptions = Subscription::query()
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->where('type', 'like', 'child-%');

        $monthlyRecurring = Child::query()
            ->whereIn('ulid', (clone $validSubscriptions)->pluck('type')->map(fn (string $type) => substr($type, 6)))
            ->join('plans', 'plans.id', '=', 'children.plan_id')
            ->sum('plans.price_monthly');

        return [
            'parents' => User::role(Role::Parent->value)->count(),
            'students' => Child::count(),
            'subscriptions' => (clone $validSubscriptions)->count(),
            'trials' => (clone $validSubscriptions)->where('stripe_status', 'trialing')->count(),
            'mrr' => Money::format((int) $monthlyRecurring),
            'unreadMessages' => ContactMessage::whereNull('read_at')->count(),
        ];
    }

    /**
     * @return Collection<int, LiveSession>
     */
    #[Computed]
    public function upcomingSessions(): Collection
    {
        return LiveSession::upcoming()->with('course', 'teacher')->limit(6)->get();
    }

    /**
     * @return Collection<int, QuizAttempt>
     */
    #[Computed]
    public function recentAttempts(): Collection
    {
        return QuizAttempt::whereNotNull('completed_at')
            ->with('child', 'quiz')
            ->latest('completed_at')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Plan>
     */
    #[Computed]
    public function planMix(): Collection
    {
        return Plan::active()->withCount('children')->get();
    }
};
?>

<div class="space-y-8">
    <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Monthly recurring revenue', $this->stats['mrr'], 'Estimated from active plans'],
            ['Active plans', $this->stats['subscriptions'], $this->stats['trials'].' on free trial'],
            ['Students', $this->stats['students'], $this->stats['parents'].' parent accounts'],
            ['Unread enquiries', $this->stats['unreadMessages'], 'From the contact form'],
        ] as [$label, $value, $hint])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <dt class="text-sm text-slate-500">{{ $label }}</dt>
                <dd class="mt-1 font-display text-3xl font-extrabold text-brand-950">{{ $value }}</dd>
                <dd class="mt-1 text-xs text-slate-500">{{ $hint }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="grid gap-8 xl:grid-cols-3">
        <x-portal.card title="Upcoming live sessions" class="xl:col-span-2">
            @can(\App\Enums\Permission::ManageLiveSessions->value)
                <x-slot:actions>
                    <a href="{{ route('admin.sessions.create') }}" wire:navigate class="btn-primary py-2">Schedule session</a>
                </x-slot:actions>
            @endcan
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-slate-500 uppercase"><tr><th class="pb-2">When</th><th class="pb-2">Session</th><th class="pb-2">Tutor</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->upcomingSessions as $session)
                        <tr wire:key="session-{{ $session->id }}">
                            <td class="py-3 whitespace-nowrap text-slate-600">{{ $session->starts_at->format('D j M, H:i') }}</td>
                            <td class="py-3 font-medium text-slate-800">{{ $session->title }}</td>
                            <td class="py-3 text-slate-600">{{ $session->teacher?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-slate-500">Nothing scheduled.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-portal.card>

        <x-portal.card title="Plan mix">
            <ul class="space-y-3">
                @foreach ($this->planMix as $plan)
                    <li class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700">{{ $plan->name }}</span>
                        <x-portal.badge tone="brand">{{ $plan->children_count }} {{ Str::plural('child', $plan->children_count) }}</x-portal.badge>
                    </li>
                @endforeach
            </ul>
        </x-portal.card>
    </div>

    <x-portal.card title="Latest quiz results">
        <table class="w-full text-left text-sm">
            <thead class="text-xs text-slate-500 uppercase"><tr><th class="pb-2">Student</th><th class="pb-2">Quiz</th><th class="pb-2">Score</th><th class="pb-2">When</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->recentAttempts as $attempt)
                    <tr wire:key="attempt-{{ $attempt->id }}">
                        <td class="py-3 font-medium text-slate-800">{{ $attempt->child->full_name }}</td>
                        <td class="py-3 text-slate-600">{{ $attempt->quiz->title }}</td>
                        <td class="py-3"><x-portal.badge :tone="$attempt->passed ? 'green' : 'amber'">{{ $attempt->percentage }}%</x-portal.badge></td>
                        <td class="py-3 text-slate-500">{{ $attempt->completed_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3 text-slate-500">No quizzes completed yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-portal.card>
</div>
