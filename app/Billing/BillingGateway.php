<?php

namespace App\Billing;

use App\Models\Child;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Boundary between the application and the payment provider. Each child has
 * their own subscription on the parent's billing account.
 */
interface BillingGateway
{
    /**
     * Start a subscription for the child and return the URL the parent should
     * visit to complete it (a hosted checkout page, or the success URL when
     * no payment step is needed).
     */
    public function startSubscription(Child $child, Plan $plan, ?Coupon $coupon, string $successUrl, string $cancelUrl): string;

    /**
     * Move an existing child subscription to a different plan.
     */
    public function swapPlan(Child $child, Plan $plan): void;

    /**
     * Cancel at the end of the current billing period.
     */
    public function cancel(Child $child): void;

    /**
     * Undo a pending cancellation during the grace period.
     */
    public function resume(Child $child): void;

    /**
     * Apply a discount to an existing child subscription.
     */
    public function applyCoupon(Child $child, Coupon $coupon): void;

    /**
     * Create or update the plan's product and recurring price with the provider.
     */
    public function syncPlan(Plan $plan): void;

    /**
     * Create the coupon and its customer-facing promotion code with the provider.
     */
    public function syncCoupon(Coupon $coupon): void;

    /**
     * URL of the provider's self-service billing portal, or null when unavailable.
     */
    public function billingPortalUrl(User $parent, string $returnUrl): ?string;

    /**
     * Recent invoices for the parent.
     *
     * @return array<int, array{id: string, date: Carbon, total: string, status: string, url: ?string}>
     */
    public function invoices(User $parent): array;
}
