<?php

namespace App\Billing;

use App\Models\Child;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Invoice;
use RuntimeException;

class StripeBillingGateway implements BillingGateway
{
    public function startSubscription(Child $child, Plan $plan, ?Coupon $coupon, string $successUrl, string $cancelUrl): string
    {
        if ($plan->stripe_price_id === null) {
            throw new RuntimeException("Plan [{$plan->slug}] has not been synced to Stripe. Run `php artisan billing:sync-plans`.");
        }

        $builder = $child->parent
            ->newSubscription($child->subscriptionType(), $plan->stripe_price_id)
            ->withMetadata([
                'child_ulid' => $child->ulid,
                'plan_slug' => $plan->slug,
                'coupon_id' => $coupon?->id,
            ]);

        if ($plan->trial_days >= 2) {
            $builder->trialDays($plan->trial_days);
        }

        if ($coupon?->stripe_promotion_code_id !== null) {
            $builder->withPromotionCode($coupon->stripe_promotion_code_id);
        }

        $checkout = $builder->checkout([
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'child_ulid' => $child->ulid,
                'coupon_id' => $coupon?->id,
            ],
        ]);

        return $checkout->url;
    }

    public function swapPlan(Child $child, Plan $plan): void
    {
        $child->subscription()?->swap($plan->stripe_price_id);
    }

    public function cancel(Child $child): void
    {
        $child->subscription()?->cancel();
    }

    public function resume(Child $child): void
    {
        $child->subscription()?->resume();
    }

    public function applyCoupon(Child $child, Coupon $coupon): void
    {
        if ($coupon->stripe_promotion_code_id === null) {
            $this->syncCoupon($coupon);
        }

        $child->subscription()?->applyPromotionCode($coupon->stripe_promotion_code_id);
    }

    public function syncPlan(Plan $plan): void
    {
        $stripe = Cashier::stripe();

        if ($plan->stripe_product_id === null) {
            $product = $stripe->products->create([
                'name' => "Marvel Academy: {$plan->name}",
                'description' => $plan->description,
                'metadata' => ['plan_slug' => $plan->slug],
            ]);

            $plan->stripe_product_id = $product->id;
        }

        $currentPrice = $plan->stripe_price_id !== null
            ? $stripe->prices->retrieve($plan->stripe_price_id)
            : null;

        if ($currentPrice === null || $currentPrice->unit_amount !== $plan->price_monthly) {
            $price = $stripe->prices->create([
                'product' => $plan->stripe_product_id,
                'unit_amount' => $plan->price_monthly,
                'currency' => config('cashier.currency'),
                'recurring' => ['interval' => 'month'],
                'metadata' => ['plan_slug' => $plan->slug],
            ]);

            if ($currentPrice !== null) {
                $stripe->prices->update($currentPrice->id, ['active' => false]);
            }

            $plan->stripe_price_id = $price->id;
        }

        $plan->save();
    }

    public function syncCoupon(Coupon $coupon): void
    {
        $stripe = Cashier::stripe();

        if ($coupon->stripe_coupon_id === null) {
            $stripeCoupon = $stripe->coupons->create(array_filter([
                'name' => $coupon->name,
                'percent_off' => $coupon->percent_off,
                'amount_off' => $coupon->amount_off,
                'currency' => $coupon->amount_off !== null ? config('cashier.currency') : null,
                'duration' => $coupon->duration,
                'duration_in_months' => $coupon->duration === 'repeating' ? $coupon->duration_in_months : null,
            ], fn ($value) => $value !== null));

            $coupon->stripe_coupon_id = $stripeCoupon->id;
        }

        if ($coupon->stripe_promotion_code_id === null) {
            $promotionCode = $stripe->promotionCodes->create(array_filter([
                'promotion' => ['type' => 'coupon', 'coupon' => $coupon->stripe_coupon_id],
                'code' => $coupon->code,
                'max_redemptions' => $coupon->max_redemptions,
                'expires_at' => $coupon->expires_at?->getTimestamp(),
            ], fn ($value) => $value !== null));

            $coupon->stripe_promotion_code_id = $promotionCode->id;
        }

        $coupon->save();
    }

    public function billingPortalUrl(User $parent, string $returnUrl): ?string
    {
        if (! $parent->hasStripeId()) {
            return null;
        }

        return $parent->billingPortalUrl($returnUrl);
    }

    public function invoices(User $parent): array
    {
        if (! $parent->hasStripeId()) {
            return [];
        }

        return $parent->invoices()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'date' => Carbon::instance($invoice->date()),
                'total' => $invoice->total(),
                'status' => (string) $invoice->status,
                'url' => $invoice->hosted_invoice_url,
            ])
            ->all();
    }
}
