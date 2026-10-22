<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    /**
     * Send the user to the portal for their role. Staff on custom roles
     * without a portal role land in the admin area.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $role = $request->user()->primaryRole();

        if ($role !== null) {
            return redirect()->route($role->dashboardRoute());
        }

        abort_unless(Gate::allows('access-admin'), 403, 'Your account has not been assigned a portal yet.');

        return redirect()->route('admin.dashboard');
    }
}
