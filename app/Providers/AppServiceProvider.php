<?php

namespace App\Providers;

use App\Models\Operator;
use Illuminate\Support\Facades\View;
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
        // Admins see a live count of operators waiting for approval in the navbar and sidebar.
        View::composer(['partials.nav', 'partials.sidebar'], function ($view) {
            $user = auth()->user();
            $view->with('pendingOperatorCount', $user?->isAdmin() ? Operator::pending()->count() : 0);
        });
    }
}
