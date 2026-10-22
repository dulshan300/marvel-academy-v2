<?php

namespace App\Listeners;

use App\Models\Coupon;
use Laravel\Cashier\Events\WebhookHandled;

class RecordCouponRedemption
{
    /**
     * Count a coupon as redeemed once Stripe confirms the checkout completed.
     */
    public function handle(WebhookHandled $event): void
    {
        if (($event->payload['type'] ?? null) !== 'checkout.session.completed') {
            return;
        }

        $couponId = $event->payload['data']['object']['metadata']['coupon_id'] ?? null;

        if ($couponId === null || $couponId === '') {
            return;
        }

        Coupon::whereKey((int) $couponId)->increment('times_redeemed');
    }
}
