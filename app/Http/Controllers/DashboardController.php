<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if (Gate::allows('access-admin-dashboard')) {
            return redirect()->route('admin.dashboard');
        }

        Gate::authorize('access-customer-dashboard');

        $user = $request->user();

        return view('dashboard', [
            'profile' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'date_of_birth' => $user->date_of_birth?->format('d.m.Y'),
                'id_number' => $user->id_number,
                'email' => $user->email,
                'email_verified' => $user->hasVerifiedEmail(),
                'phone' => $user->phone,
                'street_address' => $user->street_address,
                'city' => $user->city,
                'postal_code' => $user->postal_code,
                'member_since' => $user->created_at?->format('d.m.Y'),
                'is_active' => $user->is_active,
            ],
        ]);
    }
}
