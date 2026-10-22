<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code', 'name', 'percent_off', 'amount_off', 'duration', 'duration_in_months',
    'max_redemptions', 'expires_at', 'stripe_coupon_id', 'stripe_promotion_code_id', 'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return $this->max_redemptions === null || $this->times_redeemed < $this->max_redemptions;
    }

    /**
     * Apply the discount to an amount in pence.
     */
    public function discount(int $amount): int
    {
        if ($this->percent_off !== null) {
            return (int) round($amount * $this->percent_off / 100);
        }

        return min($amount, (int) $this->amount_off);
    }
}
