<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
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
        // Toutes les permissions ("revenue.create", "civil.birth.validate"…) passent par ce portail :
        // `@can('revenue.create')` et le middleware `can:revenue.create` fonctionnent sans policy dédiée.
        Gate::before(function (User $user, string $ability) {
            return str_contains($ability, '.') && $user->hasPermission($ability) ? true : null;
        });

        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        Carbon::setLocale(config('app.locale', 'fr'));
    }
}
