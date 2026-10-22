<?php

namespace App\Billing;

use App\Models\Child;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Stripe\Subscription as StripeSubscription;

/**
 * Development stand-in for Stripe. Subscriptions are written straight to
 * Cashier's tables so the full parent journey works without API keys.
 * Never bound in production.
 */
class LocalBillingGateway implements BillingGateway
{
    public function startSubscription(Child $child, Plan $plan, ?Coupon $coupon, string $successUrl, string $cancelUrl): string
    {
        $parent = $child->parent;
        $trialEndsAt = $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null;

        $subscription = $parent->subscriptions()->updateOrCreate(
            ['type' => $child->subscriptionType()],
            [
                'stripe_id' => 'sub_local_'.Str::random(24),
                'stripe_status' => $trialEndsAt ? StripeSubscription::STATUS_TRIALING : StripeSubscription::STATUS_ACTIVE,
                'stripe_price' => $this->priceId($plan),
                'quantity' => 1,
                'trial_ends_at' => $trialEndsAt,
                'ends_at' => null,
            ],
        );

        $subscription->items()->delete();
        $subscription->items()->create([
            'stripe_id' => 'si_local_'.Str::random(24),
            'stripe_product' => 'prod_local_'.$plan->slug,
            'stripe_price' => $this->priceId($plan),
            'quantity' => 1,
        ]);

        $coupon?->increment('times_redeemed');

        return $successUrl;
    }

    public function swapPlan(Child $child, Plan $plan): void
    {
        $subscription = $child->subscription();

        $subscription?->forceFill(['stripe_price' => $this->priceId($plan)])->save();
        $subscription?->items()->update(['stripe_price' => $this->priceId($plan), 'stripe_product' => 'prod_local_'.$plan->slug]);
    }

    public function cancel(Child $child): void
    {
        $subscription = $child->subscription();

        $subscription?->forceFill(['ends_at' => $this->periodEnd($subscription)])->save();
    }

    public function resume(Child $child): void
    {
        $child->subscription()?->forceFill(['ends_at' => null])->save();
    }

    public function applyCoupon(Child $child, Coupon $coupon): void
    {
        $coupon->increment('times_redeemed');
    }

    public function syncPlan(Plan $plan): void
    {
        $plan->forceFill(['stripe_price_id' => $this->priceId($plan)])->save();
    }

    public function syncCoupon(Coupon $coupon): void
    {
        //
    }

    public function billingPortalUrl(User $parent, string $returnUrl): ?string
    {
        return null;
    }

    public function invoices(User $parent): array
    {
        return [];
    }

    private function priceId(Plan $plan): string
    {
        return $plan->stripe_price_id ?? 'price_local_'.$plan->slug;
    }

    private function periodEnd(Subscription $subscription): Carbon
    {
        if ($subscription->onTrial()) {
            return $subscription->trial_ends_at;
        }

        $end = $subscription->created_at->copy();

        while ($end->isPast()) {
            $end->addMonth();
        }

        return $end;
    }
}
