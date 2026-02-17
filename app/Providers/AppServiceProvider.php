<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

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
        if (app()->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Gate::define('viewPulse', function (User $user) {
            return app()->environment('local') || $user->is_admin;
        });

        \Laravel\Pulse\Facades\Pulse::user(fn(User $user) => [
            'name' => $user->name,
            'extra' => $user->email,
            'avatar' => $user->avatar ? url('media/' . $user->avatar) : null,
        ]);
    }
}
