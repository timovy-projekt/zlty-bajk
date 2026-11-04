<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;


class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): RedirectResponse|View
    {
        if (Gate::allows('access-admin-dashboard')) {
            return redirect()->route('admin.dashboard');
        }

        Gate::authorize('access-customer-dashboard');

        return view('dashboard', ['profile' => Auth::user()]);
    }
}
