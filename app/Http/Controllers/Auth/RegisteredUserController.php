<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $today = today();

        return view('auth.register', [
            'latestBirthDate' => $today->copy()->subYearsNoOverflow(RegisterRequest::MINIMUM_AGE)->toDateString(),
            'adultBirthDate' => $today->copy()->subYearsNoOverflow(RegisterRequest::ADULT_AGE)->toDateString(),
            'currentDate' => $today->toDateString(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = new User($request->safe()->only([
                'first_name',
                'last_name',
                'phone',
                'email',
                'street_address',
                'city',
                'postal_code',
                'date_of_birth',
                'id_number',
            ]));

            $user->password = $request->validated('password');
            $user->role_id = User::ROLE_CUSTOMER;
            $user->is_active = true;
            $user->save();

            if ($request->validated('guardian_first_name') !== null) {
                $guardianDetails = [];

                foreach (['first_name', 'last_name', 'email', 'phone', 'street_address', 'city', 'postal_code', 'date_of_birth', 'id_number'] as $field) {
                    $guardianDetails[$field] = $request->validated('guardian_'.$field);
                }

                if ($request->boolean('guardian_same_address')) {
                    foreach (['street_address', 'city', 'postal_code'] as $field) {
                        $guardianDetails[$field] = $user->{$field};
                    }
                }

                $user->guardian()->create($guardianDetails);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
