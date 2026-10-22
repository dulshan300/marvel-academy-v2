<?php

namespace Tests\Feature\Listeners;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Events\WebhookHandled;
use Tests\TestCase;

class RecordCouponRedemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_checkout_with_a_coupon_increments_redemptions(): void
    {
        $coupon = Coupon::factory()->create();

        event(new WebhookHandled([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['metadata' => ['coupon_id' => (string) $coupon->id]]],
        ]));

        $this->assertSame(1, $coupon->fresh()->times_redeemed);
    }

    public function test_other_webhook_types_are_ignored(): void
    {
        $coupon = Coupon::factory()->create();

        event(new WebhookHandled([
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => ['metadata' => ['coupon_id' => (string) $coupon->id]]],
        ]));

        $this->assertSame(0, $coupon->fresh()->times_redeemed);
    }
}
