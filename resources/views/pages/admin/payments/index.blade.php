<?php

use App\Billing\BillingGateway;
use App\Enums\Permission;
use App\Models\Child;
use App\Models\Coupon;
use App\Models\Plan;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Laravel\Cashier\Subscription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::portal')] #[Title('Payments')] class extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $status = '';

    public string $code = '';

    public string $name = '';

    public string $discountType = 'percent';

    public string $discountValue = '';

    public string $duration = 'once';

    public ?int $duration_in_months = null;

    public ?int $max_redemptions = null;

    public string $expires_at = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function subscriptions(): LengthAwarePaginator
    {
        $subscriptions = Subscription::query()
            ->where('type', 'like', 'child-%')
            ->when($this->status !== '', fn ($query) => $query->where('stripe_status', $this->status))
            ->with('owner')
            ->latest()
            ->paginate(20, pageName: 'subscriptions');

        $children = Child::with('plan')
            ->whereIn('ulid', $subscriptions->getCollection()->map(fn (Subscription $subscription) => substr($subscription->type, 6)))
            ->get()
            ->keyBy('ulid');

        $subscriptions->getCollection()->each(fn (Subscription $subscription) => $subscription->setAttribute('child', $children->get(substr($subscription->type, 6))));

        return $subscriptions;
    }

    /**
     * @return Collection<int, Coupon>
     */
    #[Computed]
    public function coupons(): Collection
    {
        return Coupon::latest()->get();
    }

    /**
     * @return Collection<int, Plan>
     */
    #[Computed]
    public function plans(): Collection
    {
        return Plan::active()->withCount('children')->get();
    }

    public function createCoupon(BillingGateway $billing): void
    {
        $this->authorize(Permission::ManagePayments->value);

        $this->code = mb_strtoupper(preg_replace('/\s+/', '', $this->code));

        $validated = $this->validate([
            'code' => ['required', 'string', 'alpha_dash', 'min:3', 'max:40', Rule::unique('coupons', 'code')],
            'name' => ['required', 'string', 'max:120'],
            'discountType' => ['required', Rule::in(['percent', 'amount'])],
            'discountValue' => $this->discountType === 'percent'
                ? ['required', 'integer', 'between:1,100']
                : ['required', 'numeric', 'min:0.5', 'max:1000', 'decimal:0,2'],
            'duration' => ['required', Rule::in(['once', 'repeating', 'forever'])],
            'duration_in_months' => [Rule::requiredIf($this->duration === 'repeating'), 'nullable', 'integer', 'between:1,24'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $coupon = Coupon::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'percent_off' => $validated['discountType'] === 'percent' ? (int) $validated['discountValue'] : null,
            'amount_off' => $validated['discountType'] === 'amount' ? (int) round($validated['discountValue'] * 100) : null,
            'duration' => $validated['duration'],
            'duration_in_months' => $validated['duration'] === 'repeating' ? $validated['duration_in_months'] : null,
            'max_redemptions' => $validated['max_redemptions'],
            'expires_at' => $validated['expires_at'] ? now()->parse($validated['expires_at'])->endOfDay() : null,
            'is_active' => true,
        ]);

        try {
            $billing->syncCoupon($coupon);
            session()->flash('status', "Coupon {$coupon->code} created.");
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('status', "Coupon {$coupon->code} saved, but could not be created with the payment provider yet. Run billing:sync-plans --coupons once Stripe is configured.");
        }

        $this->reset('code', 'name', 'discountValue', 'duration_in_months', 'max_redemptions', 'expires_at');
        unset($this->coupons);
    }

    public function toggleCoupon(int $couponId): void
    {
        $this->authorize(Permission::ManagePayments->value);

        $coupon = Coupon::findOrFail($couponId);
        $coupon->update(['is_active' => ! $coupon->is_active]);
        unset($this->coupons);
    }

    public function syncPlans(BillingGateway $billing): void
    {
        $this->authorize(Permission::ManagePayments->value);

        try {
            $this->plans->each(fn (Plan $plan) => $billing->syncPlan($plan));
            session()->flash('status', 'Plans synced with the payment provider.');
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('status', 'Plan sync failed: '.$exception->getMessage());
        }

        unset($this->plans);
    }
};
?>

<div class="space-y-8">
    <x-portal.card title="Plans">
        <x-slot:actions>
            <button type="button" wire:click="syncPlans" class="btn-ghost py-2 text-brand-700">Sync with Stripe</button>
        </x-slot:actions>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->plans as $plan)
                <div class="rounded-xl bg-slate-50 p-4" wire:key="plan-{{ $plan->id }}">
                    <p class="font-semibold text-slate-800">{{ $plan->name }}</p>
                    <p class="text-sm text-slate-600">{{ Money::format($plan->price_monthly) }}/month · {{ $plan->children_count }} {{ Str::plural('child', $plan->children_count) }}</p>
                    <p class="mt-2 truncate text-xs {{ $plan->stripe_price_id ? 'text-emerald-700' : 'text-amber-700' }}">{{ $plan->stripe_price_id ?? 'Not synced' }}</p>
                </div>
            @endforeach
        </div>
    </x-portal.card>

    <div class="grid gap-8 2xl:grid-cols-5">
        <x-portal.card title="Coupons" class="2xl:col-span-3">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-slate-500 uppercase"><tr><th class="pb-2">Code</th><th class="pb-2">Discount</th><th class="pb-2">Redeemed</th><th class="pb-2">Expires</th><th class="pb-2">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->coupons as $coupon)
                            <tr wire:key="coupon-{{ $coupon->id }}">
                                <td class="py-3"><p class="font-mono font-semibold text-slate-800">{{ $coupon->code }}</p><p class="text-xs text-slate-500">{{ $coupon->name }}</p></td>
                                <td class="py-3 text-slate-600">
                                    {{ $coupon->percent_off ? $coupon->percent_off.'%' : Money::format((int) $coupon->amount_off) }} off
                                    <span class="text-xs text-slate-500">({{ $coupon->duration === 'repeating' ? $coupon->duration_in_months.' months' : $coupon->duration }})</span>
                                </td>
                                <td class="py-3 text-slate-600">{{ $coupon->times_redeemed }}{{ $coupon->max_redemptions ? ' / '.$coupon->max_redemptions : '' }}</td>
                                <td class="py-3 text-slate-600">{{ $coupon->expires_at?->format('j M Y') ?? 'Never' }}</td>
                                <td class="py-3">
                                    <button type="button" wire:click="toggleCoupon({{ $coupon->id }})">
                                        <x-portal.badge :tone="$coupon->isRedeemable() ? 'green' : 'slate'">{{ $coupon->isRedeemable() ? 'Active' : ($coupon->is_active ? 'Used up / expired' : 'Disabled') }}</x-portal.badge>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-3 text-slate-500">No coupons yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-portal.card>

        <x-portal.card title="New coupon" class="2xl:col-span-2">
            <form wire:submit="createCoupon" class="grid gap-4 sm:grid-cols-2" novalidate>
                <div>
                    <label for="code" class="form-label">Code</label>
                    <input id="code" type="text" wire:model="code" class="form-input font-mono uppercase" placeholder="SIBLING10">
                    @error('code') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="coupon-name" class="form-label">Internal name</label>
                    <input id="coupon-name" type="text" wire:model="name" class="form-input">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="discountType" class="form-label">Discount</label>
                    <select id="discountType" wire:model.live="discountType" class="form-input">
                        <option value="percent">Percentage off</option>
                        <option value="amount">Fixed amount off (£)</option>
                    </select>
                </div>
                <div>
                    <label for="discountValue" class="form-label">{{ $discountType === 'percent' ? 'Percent' : 'Amount (£)' }}</label>
                    <input id="discountValue" type="number" step="{{ $discountType === 'percent' ? '1' : '0.01' }}" wire:model="discountValue" class="form-input">
                    @error('discountValue') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="duration" class="form-label">Applies</label>
                    <select id="duration" wire:model.live="duration" class="form-input">
                        <option value="once">First payment only</option>
                        <option value="repeating">For a number of months</option>
                        <option value="forever">Every payment</option>
                    </select>
                </div>
                @if ($duration === 'repeating')
                    <div>
                        <label for="duration_in_months" class="form-label">Months</label>
                        <input id="duration_in_months" type="number" min="1" wire:model="duration_in_months" class="form-input">
                        @error('duration_in_months') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div>
                    <label for="max_redemptions" class="form-label">Max redemptions</label>
                    <input id="max_redemptions" type="number" min="1" wire:model="max_redemptions" class="form-input" placeholder="Unlimited">
                    @error('max_redemptions') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="expires_at" class="form-label">Expires</label>
                    <input id="expires_at" type="date" wire:model="expires_at" class="form-input">
                    @error('expires_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="btn-primary">Create coupon</button>
                </div>
            </form>
        </x-portal.card>
    </div>

    <x-portal.card title="Subscriptions">
        <x-slot:actions>
            <select wire:model.live="status" class="form-input py-2" aria-label="Filter by status">
                <option value="">All statuses</option>
                @foreach (['trialing', 'active', 'past_due', 'canceled', 'incomplete'] as $option)
                    <option value="{{ $option }}">{{ Str::headline($option) }}</option>
                @endforeach
            </select>
        </x-slot:actions>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-slate-500 uppercase"><tr><th class="pb-2">Child</th><th class="pb-2">Parent</th><th class="pb-2">Plan</th><th class="pb-2">Status</th><th class="pb-2">Started</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->subscriptions as $subscription)
                        <tr wire:key="subscription-{{ $subscription->id }}">
                            <td class="py-3 font-medium text-slate-800">{{ $subscription->child?->full_name ?? 'Removed child' }}</td>
                            <td class="py-3 text-slate-600">
                                @if ($subscription->owner)
                                    <a href="{{ route('admin.families.show', $subscription->owner) }}" wire:navigate class="text-brand-700">{{ $subscription->owner->name }}</a>
                                @endif
                            </td>
                            <td class="py-3 text-slate-600">{{ $subscription->child?->plan?->name ?? '—' }}</td>
                            <td class="py-3"><x-portal.subscription-status :subscription="$subscription" /></td>
                            <td class="py-3 text-slate-500">{{ $subscription->created_at->format('j M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-3 text-slate-500">No subscriptions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $this->subscriptions->links() }}</div>
    </x-portal.card>
</div>
