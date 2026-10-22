<?php

namespace App\Console\Commands;

use App\Billing\BillingGateway;
use App\Models\Coupon;
use App\Models\Plan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('billing:sync-plans {--coupons : Also create any active coupons with the provider}')]
#[Description('Create or update a provider product and monthly price for every active plan')]
class SyncBillingPlans extends Command
{
    public function handle(BillingGateway $billing): int
    {
        $failed = false;

        foreach (Plan::active()->get() as $plan) {
            try {
                $billing->syncPlan($plan);
                $this->components->info("{$plan->name}: {$plan->stripe_price_id}");
            } catch (Throwable $exception) {
                $failed = true;
                $this->components->error("{$plan->name}: {$exception->getMessage()}");
            }
        }

        if ($this->option('coupons')) {
            foreach (Coupon::where('is_active', true)->get() as $coupon) {
                try {
                    $billing->syncCoupon($coupon);
                    $this->components->info("Coupon {$coupon->code} synced");
                } catch (Throwable $exception) {
                    $failed = true;
                    $this->components->error("Coupon {$coupon->code}: {$exception->getMessage()}");
                }
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
