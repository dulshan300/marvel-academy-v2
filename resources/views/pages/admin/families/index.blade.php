<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::portal')] #[Title('Families')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function parents(): LengthAwarePaginator
    {
        $term = '%'.mb_strtolower(trim($this->search)).'%';

        return User::role(Role::Parent->value)
            ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereRaw('LOWER(name) LIKE ?', [$term])
                ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                ->orWhereHas('children', fn ($children) => $children->whereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", [$term]))))
            ->with(['children.plan', 'subscriptions'])
            ->withCount('children')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);
    }
};
?>

<div class="space-y-6">
    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search parents, emails or children…" class="form-input max-w-md" aria-label="Search families">

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                <tr><th class="px-5 py-3">Parent</th><th class="px-5 py-3">Children</th><th class="px-5 py-3">Plans</th><th class="px-5 py-3">Joined</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->parents as $parent)
                    <tr wire:key="parent-{{ $parent->id }}">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-slate-800">{{ $parent->name }} @unless ($parent->is_active) <x-portal.badge tone="red">Deactivated</x-portal.badge> @endunless</p>
                            <p class="text-xs text-slate-500">{{ $parent->email }}</p>
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $parent->children->map(fn ($child) => $child->first_name)->join(', ') ?: '—' }}</td>
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($parent->children as $child)
                                    <x-portal.subscription-status :subscription="$parent->subscription($child->subscriptionType())" />
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $parent->created_at->format('j M Y') }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('admin.families.show', $parent) }}" wire:navigate class="font-semibold text-brand-700">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No families found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->parents->links() }}
</div>
