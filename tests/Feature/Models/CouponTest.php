<?php

namespace Tests\Feature\Models;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_coupon_without_limits_is_redeemable(): void
    {
        $coupon = Coupon::factory()->create();

        $this->assertTrue($coupon->isRedeemable());
    }

    public function test_inactive_coupon_is_not_redeemable(): void
    {
        $coupon = Coupon::factory()->create(['is_active' => false]);

        $this->assertFalse($coupon->isRedeemable());
    }

    public function test_expired_coupon_is_not_redeemable(): void
    {
        $coupon = Coupon::factory()->expired()->create();

        $this->assertFalse($coupon->isRedeemable());
    }

    public function test_coupon_at_its_redemption_limit_is_not_redeemable(): void
    {
        $coupon = Coupon::factory()->create(['max_redemptions' => 3]);
        $coupon->forceFill(['times_redeemed' => 3])->save();

        $this->assertFalse($coupon->isRedeemable());
    }

    public function test_percentage_coupon_discounts_proportionally(): void
    {
        $coupon = Coupon::factory()->make(['percent_off' => 25]);

        $this->assertSame(2475, $coupon->discount(9900));
    }

    public function test_fixed_amount_coupon_never_discounts_more_than_the_amount(): void
    {
        $coupon = Coupon::factory()->amountOff(5000)->make();

        $this->assertSame(5000, $coupon->discount(9900));
        $this->assertSame(3000, $coupon->discount(3000));
    }
}
