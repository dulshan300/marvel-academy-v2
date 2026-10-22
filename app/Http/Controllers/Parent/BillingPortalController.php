<?php

namespace App\Http\Controllers\Parent;

use App\Billing\BillingGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingPortalController extends Controller
{
    /**
     * Send the parent to the provider's hosted portal to manage cards and invoices.
     */
    public function __invoke(Request $request, BillingGateway $billing): RedirectResponse
    {
        $url = $billing->billingPortalUrl($request->user(), route('parent.billing'));

        if ($url === null) {
            return redirect()->route('parent.billing')->with('status', 'Online payment details are not available for this account yet.');
        }

        return redirect()->away($url);
    }
}
