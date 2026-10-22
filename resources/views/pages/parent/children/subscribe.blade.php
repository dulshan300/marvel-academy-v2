<?php

use App\Actions\Billing\StartChildSubscription;
use App\Models\Child;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Plan;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Choose a plan')] class extends Component
{
    #[Locked]
    public Child $child;

    public string $planSlug = '';

    /** @var array<int, int> */
    public array $courseIds = [];

    public string $couponCode = '';

    public ?string $appliedCouponCode = null;

    public function mount(Child $child): void
    {
        $this->authorize('update', $child);

        $this->child = $child;

        if ($child->hasActiveSubscription()) {
            $this->redirectRoute('parent.children.courses', $child, navigate: true);

            return;
        }
        $this->planSlug = session('checkout.plan')
            ?? $this->plans->firstWhere('is_featured', true)?->slug
            ?? $this->plans->first()?->slug
            ?? '';
        $this->courseIds = $child->activeCourses()->pluck('courses.id')->all();
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
     * @return Collection<int, Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return Course::published()
            ->where('key_stage', $this->child->key_stage)
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function selectedPlan(): ?Plan
    {
        return $this->plans->firstWhere('slug', $this->planSlug);
    }

    #[Computed]
    public function appliedCoupon(): ?Coupon
    {
        return $this->appliedCouponCode ? Coupon::findByCode($this->appliedCouponCode) : null;
    }

    public function updatedCourseIds(): void
    {
        $this->courseIds = array_values(array_map('intval', $this->courseIds));
    }

    public function updatedPlanSlug(): void
    {
        $limit = $this->selectedPlan?->subjects_included ?? 0;

        $this->courseIds = array_slice($this->courseIds, 0, $limit);
    }

    public function applyCoupon(StartChildSubscription $startSubscription): void
    {
        $this->resetErrorBag('couponCode');

        try {
            $coupon = $startSubscription->resolveCoupon($this->couponCode);
        } catch (ValidationException $exception) {
            $this->appliedCouponCode = null;

            throw $exception;
        }

        $this->appliedCouponCode = $coupon?->code;
    }

    public function removeCoupon(): void
    {
        $this->reset('couponCode', 'appliedCouponCode');
    }

    public function checkout(StartChildSubscription $startSubscription): void
    {
        $this->authorize('update', $this->child);

        $plan = $this->selectedPlan;

        if ($plan === null) {
            $this->addError('plan', 'Choose a plan.');

            return;
        }

        $url = $startSubscription->handle($this->child, $plan, $this->courseIds, $this->appliedCouponCode);

        session()->forget('checkout.plan');

        $this->redirect($url);
    }
};
?>

@php
    $plan = $this->selectedPlan;
    $coupon = $this->appliedCoupon;
    $discount = $plan && $coupon ? $coupon->discount($plan->price_monthly) : 0;
@endphp

<div class="grid gap-8 xl:grid-cols-3">
    <div class="space-y-8 xl:col-span-2">
        <x-portal.card :title="'1. Choose a plan for '.$child->first_name">
            <fieldset>
                <legend class="sr-only">Plan</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($this->plans as $option)
                        <label wire:key="plan-{{ $option->id }}" @class([
                            'relative flex cursor-pointer flex-col rounded-xl border-2 p-5 transition',
                            'border-brand-600 bg-brand-50' => $planSlug === $option->slug,
                            'border-slate-200 hover:border-brand-300' => $planSlug !== $option->slug,
                        ])>
                            <input type="radio" wire:model.live="planSlug" value="{{ $option->slug }}" class="sr-only">
                            @if ($option->is_featured)
                                <span class="absolute top-4 right-4"><x-portal.badge tone="amber">Most popular</x-portal.badge></span>
                            @endif
                            <span class="font-bold text-brand-950">{{ $option->name }}</span>
                            <span class="mt-1 font-display text-2xl font-extrabold text-brand-900">{{ Money::format($option->price_monthly) }}<span class="text-sm font-medium text-slate-500">/month</span></span>
                            <span class="mt-1 text-sm text-slate-500">{{ $option->hours_per_month }} live hours · {{ Money::format($option->hourlyRate()) }}/hour</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @error('plan') <p class="form-error">{{ $message }}</p> @enderror
        </x-portal.card>

        <x-portal.card :title="'2. Choose '.($plan ? 'up to '.$plan->subjects_included.' '.Str::plural('course', $plan->subjects_included) : 'courses')">
            <p class="mb-4 text-sm text-slate-600">Showing {{ $child->key_stage->label() }} courses for Year {{ $child->year_group }}.</p>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($this->courses as $course)
                    @php $checked = in_array($course->id, $courseIds, true); @endphp
                    @php $disabled = ! $checked && $plan && count($courseIds) >= $plan->subjects_included; @endphp
                    <label wire:key="course-{{ $course->id }}" @class([
                        'flex items-center gap-3 rounded-xl border p-4 transition',
                        'border-brand-600 bg-brand-50' => $checked,
                        'border-slate-200' => ! $checked,
                        'cursor-pointer hover:border-brand-300' => ! $disabled,
                        'cursor-not-allowed opacity-50' => $disabled,
                    ])>
                        <input type="checkbox" wire:model.live="courseIds" value="{{ $course->id }}" @disabled($disabled) class="size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                        <span class="font-semibold text-slate-800">{{ $course->key_stage->label() }} {{ $course->title }}</span>
                    </label>
                @endforeach
            </div>
            @error('courses') <p class="form-error">{{ $message }}</p> @enderror
        </x-portal.card>
    </div>

    <aside class="xl:sticky xl:top-24 xl:self-start">
        <x-portal.card title="Summary">
            @if ($plan)
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt>{{ $plan->name }} plan</dt><dd class="font-semibold">{{ Money::format($plan->price_monthly) }}</dd></div>
                    @if ($coupon)
                        <div class="flex justify-between text-emerald-700"><dt>Discount ({{ $coupon->code }})</dt><dd class="font-semibold">−{{ Money::format($discount) }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-slate-100 pt-3 text-base"><dt class="font-bold text-brand-950">Monthly total</dt><dd class="font-extrabold text-brand-950">{{ Money::format($plan->price_monthly - $discount) }}</dd></div>
                </dl>
                @if ($plan->trial_days > 0)
                    <p class="mt-3 rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-800">Free for {{ $plan->trial_days }} days. First payment on {{ now()->addDays($plan->trial_days)->format('j F Y') }}.</p>
                @endif
            @endif

            <div class="mt-6">
                <label for="coupon" class="form-label">Discount code</label>
                @if ($coupon)
                    <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <span><strong>{{ $coupon->code }}</strong> applied</span>
                        <button type="button" wire:click="removeCoupon" class="font-semibold underline">Remove</button>
                    </div>
                @else
                    <div class="flex gap-2">
                        <input id="coupon" type="text" wire:model="couponCode" wire:keydown.enter.prevent="applyCoupon" class="form-input uppercase" autocomplete="off">
                        <button type="button" wire:click="applyCoupon" class="btn-ghost shrink-0 text-brand-700">Apply</button>
                    </div>
                @endif
                @error('couponCode') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <button type="button" wire:click="checkout" class="btn-primary mt-6 w-full py-3.5" @disabled($courseIds === [])>
                <span wire:loading.remove wire:target="checkout">Continue to secure checkout</span>
                <span wire:loading wire:target="checkout">Preparing checkout…</span>
            </button>
            <p class="mt-3 text-center text-xs text-slate-500">Payments are processed securely by Stripe. Cancel any time.</p>
        </x-portal.card>
    </aside>
</div>
