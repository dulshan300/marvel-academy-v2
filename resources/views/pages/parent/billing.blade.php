<?php

use App\Actions\Billing\StartChildSubscription;
use App\Billing\BillingGateway;
use App\Models\Child;
use App\Models\Plan;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Billing & plans')] class extends Component
{
    /** @var array<string, string> Coupon code being entered, keyed by child ULID. */
    public array $couponCodes = [];

    /** @var array<string, string> Plan slug selected for a plan change, keyed by child ULID. */
    public array $newPlans = [];

    /**
     * @return Collection<int, Child>
     */
    #[Computed]
    public function children(): Collection
    {
        $parent = auth()->user()->loadMissing('subscriptions.items');

        return $parent->children()->with('plan')->orderBy('first_name')->get();
    }

    /**
     * @return Collection<int, Plan>
     */
    #[Computed]
    public function plans(): Collection
    {
        return Plan::active()->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function invoices(): array
    {
        return app(BillingGateway::class)->invoices(auth()->user());
    }

    public function cancel(string $childUlid, BillingGateway $billing): void
    {
        $child = $this->findChild($childUlid);

        $billing->cancel($child);

        session()->flash('status', "{$child->first_name}'s plan will end at the close of the current billing period.");
        $this->redirectRoute('parent.billing', navigate: true);
    }

    public function resume(string $childUlid, BillingGateway $billing): void
    {
        $child = $this->findChild($childUlid);

        $billing->resume($child);

        session()->flash('status', "{$child->first_name}'s plan has been resumed.");
        $this->redirectRoute('parent.billing', navigate: true);
    }

    public function changePlan(string $childUlid, BillingGateway $billing): void
    {
        $child = $this->findChild($childUlid);
        $plan = $this->plans->firstWhere('slug', $this->newPlans[$childUlid] ?? null);

        if ($plan === null || $plan->is($child->plan)) {
            $this->addError("newPlans.{$childUlid}", 'Choose a different plan.');

            return;
        }

        $activeCourses = $child->activeCourses()->count();

        if ($activeCourses > $plan->subjects_included) {
            $this->addError("newPlans.{$childUlid}", "Remove some courses first: {$child->first_name} has {$activeCourses} and {$plan->name} includes {$plan->subjects_included}.");

            return;
        }

        $billing->swapPlan($child, $plan);
        $child->plan()->associate($plan)->save();

        session()->flash('status', "{$child->first_name} is now on the {$plan->name} plan.");
        $this->redirectRoute('parent.billing', navigate: true);
    }

    public function applyCoupon(string $childUlid, BillingGateway $billing, StartChildSubscription $startSubscription): void
    {
        $child = $this->findChild($childUlid);

        try {
            $coupon = $startSubscription->resolveCoupon($this->couponCodes[$childUlid] ?? '');
        } catch (ValidationException) {
            $this->addError("couponCodes.{$childUlid}", 'That discount code is not valid or has expired.');

            return;
        }

        if ($coupon === null) {
            $this->addError("couponCodes.{$childUlid}", 'Enter a discount code.');

            return;
        }

        $billing->applyCoupon($child, $coupon);

        session()->flash('status', "Discount {$coupon->code} applied to {$child->first_name}'s plan.");
        $this->redirectRoute('parent.billing', navigate: true);
    }

    private function findChild(string $childUlid): Child
    {
        $child = Child::where('ulid', $childUlid)->firstOrFail();

        $this->authorize('update', $child);

        return $child;
    }
};
?>

<div class="space-y-8">
    @if (auth()->user()->hasStripeId())
        <div class="flex justify-end">
            <a href="{{ route('parent.billing.portal') }}" class="btn-secondary">Update payment details</a>
        </div>
    @endif

    @forelse ($this->children as $child)
        @php($subscription = $child->subscription())
        <x-portal.card wire:key="billing-{{ $child->ulid }}">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <h2 class="text-lg font-bold text-brand-950">{{ $child->full_name }}</h2>
                    <p class="text-sm text-slate-500">
                        {{ $child->plan?->name ?? 'No plan' }}
                        @if ($child->plan) · {{ Money::format($child->plan->price_monthly) }}/month @endif
                    </p>
                </div>
                <x-portal.subscription-status :subscription="$subscription" />
            </div>

            @if ($subscription === null || ! $subscription->valid())
                <a href="{{ route('parent.children.subscribe', $child) }}" wire:navigate class="btn-primary mt-6">Choose a plan</a>
            @else
                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div>
                        <label for="plan-{{ $child->ulid }}" class="form-label">Change plan</label>
                        <div class="flex gap-2">
                            <select id="plan-{{ $child->ulid }}" wire:model="newPlans.{{ $child->ulid }}" class="form-input">
                                <option value="">Select a plan</option>
                                @foreach ($this->plans as $plan)
                                    <option value="{{ $plan->slug }}" @disabled($plan->is($child->plan))>{{ $plan->name }} · {{ Money::format($plan->price_monthly) }}/month</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="changePlan('{{ $child->ulid }}')" class="btn-ghost shrink-0 text-brand-700">Change</button>
                        </div>
                        @error("newPlans.{$child->ulid}") <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="coupon-{{ $child->ulid }}" class="form-label">Discount code</label>
                        <div class="flex gap-2">
                            <input id="coupon-{{ $child->ulid }}" type="text" wire:model="couponCodes.{{ $child->ulid }}" class="form-input uppercase" autocomplete="off">
                            <button type="button" wire:click="applyCoupon('{{ $child->ulid }}')" class="btn-ghost shrink-0 text-brand-700">Apply</button>
                        </div>
                        @error("couponCodes.{$child->ulid}") <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    @if ($subscription->onGracePeriod())
                        <button type="button" wire:click="resume('{{ $child->ulid }}')" class="btn-secondary">Resume plan</button>
                    @else
                        <button
                            type="button"
                            wire:click="cancel('{{ $child->ulid }}')"
                            wire:confirm="Cancel {{ $child->first_name }}'s plan? Access continues until the end of the billing period."
                            class="text-sm font-semibold text-red-600 hover:text-red-800"
                        >Cancel plan</button>
                    @endif
                </div>
            @endif
        </x-portal.card>
    @empty
        <x-portal.card>
            <p class="text-slate-600">Add a child to choose a plan.</p>
            <a href="{{ route('parent.children.create') }}" wire:navigate class="btn-primary mt-4">Add a child</a>
        </x-portal.card>
    @endforelse

    @if ($this->invoices !== [])
        <x-portal.card title="Invoices">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-slate-500 uppercase">
                    <tr><th class="py-2">Date</th><th class="py-2">Total</th><th class="py-2">Status</th><th class="py-2"><span class="sr-only">Download</span></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($this->invoices as $invoice)
                        <tr wire:key="invoice-{{ $invoice['id'] }}">
                            <td class="py-3">{{ $invoice['date']->format('j M Y') }}</td>
                            <td class="py-3 font-semibold">{{ $invoice['total'] }}</td>
                            <td class="py-3"><x-portal.badge :tone="$invoice['status'] === 'paid' ? 'green' : 'amber'">{{ ucfirst($invoice['status']) }}</x-portal.badge></td>
                            <td class="py-3 text-right">@if ($invoice['url'])<a href="{{ $invoice['url'] }}" target="_blank" rel="noopener" class="font-semibold text-brand-700">View</a>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-portal.card>
    @endif
</div>
