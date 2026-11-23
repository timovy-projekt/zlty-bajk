<?php

namespace App\Policies;

use App\Models\RentalApplication;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RentalApplicationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && $user->hasVerifiedEmail()
            && ($user->isCustomer() || $user->isAdmin());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RentalApplication $rentalApplication): Response
    {
        if (! $this->viewAny($user)) {
            return Response::deny();
        }

        if ($user->isAdmin() || $rentalApplication->user_id === $user->id) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }
}
