<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Send the user to the portal for their role.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $role = $request->user()->primaryRole();

        abort_if($role === null, 403, 'Your account has not been assigned a portal yet.');

        return redirect()->route($role->dashboardRoute());
    }
}
