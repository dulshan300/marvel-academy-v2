<?php

namespace App\Actions\Billing;

use App\Actions\Children\AssignCourses;
use App\Billing\BillingGateway;
use App\Models\Child;
use App\Models\Coupon;
use App\Models\Plan;
use Illuminate\Validation\ValidationException;

class StartChildSubscription
{
    public function __construct(
        private BillingGateway $billing,
        private AssignCourses $assignCourses,
    ) {}

    /**
     * Save the child's plan and courses, then start the subscription and return
     * the URL to send the parent to.
     *
     * @param  array<int, int>  $courseIds
     *
     * @throws ValidationException
     */
    public function handle(Child $child, Plan $plan, array $courseIds, ?string $couponCode = null): string
    {
        if ($child->hasActiveSubscription()) {
            throw ValidationException::withMessages(['plan' => "{$child->first_name} already has an active plan. Change it from the billing page."]);
        }

        if (! $plan->is_active) {
            throw ValidationException::withMessages(['plan' => 'That plan is no longer available.']);
        }

        $coupon = $this->resolveCoupon($couponCode);

        $this->assignCourses->handle($child, $plan, $courseIds);

        return $this->billing->startSubscription(
            $child,
            $plan,
            $coupon,
            route('parent.checkout.success', $child),
            route('parent.children.subscribe', $child),
        );
    }

    /**
     * @throws ValidationException
     */
    public function resolveCoupon(?string $code): ?Coupon
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $coupon = Coupon::findByCode($code);

        if ($coupon === null || ! $coupon->isRedeemable()) {
            throw ValidationException::withMessages(['couponCode' => 'That discount code is not valid or has expired.']);
        }

        return $coupon;
    }
}
