<?php

namespace App\Providers;

use App\Models\User;
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
        // A super admin holds every permission, including ones added later, so
        // a new permission never has to be granted to the role by hand.
        // Returning null (not false) leaves other checks to the normal path.
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole(User::SUPER_ADMIN) ? true : null;
        });
    }
}
