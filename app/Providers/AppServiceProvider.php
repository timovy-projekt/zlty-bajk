<?php

namespace App\Providers;

use App\Models\RentalApplication;
use App\Models\User;
use App\Policies\RentalApplicationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(RentalApplication::class, RentalApplicationPolicy::class);

        Gate::define('access-admin-dashboard', fn (User $user): bool => $user->is_active && $user->isAdmin());

        Gate::define('access-customer-dashboard', fn (User $user): bool => $user->is_active && $user->isCustomer());
    }
}
