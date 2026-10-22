<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CheckoutSuccessController extends Controller
{
    /**
     * Landing point after checkout. Stripe confirms the subscription by webhook,
     * so it may still be pending for a few seconds.
     */
    public function __invoke(Child $child): RedirectResponse
    {
        Gate::authorize('update', $child);

        $message = $child->hasActiveSubscription()
            ? "You're all set! {$child->first_name}'s plan is active."
            : "Thanks! We're confirming {$child->first_name}'s payment, which usually takes a few seconds.";

        return redirect()->route('parent.dashboard')->with('status', $message);
    }
}
