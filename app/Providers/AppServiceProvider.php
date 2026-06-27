<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // One dynamic gate: super_admin passes everything; any other ability name is
        // resolved as a permission code against the user's roles + direct grants.
        // ponytail: single Gate::before instead of one Gate::define per permission.
        Gate::before(function (User $user, string $ability) {
            return $user->hasPermission($ability) ?: null;
        });
    }
}
