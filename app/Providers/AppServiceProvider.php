<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Limits are per account (email) and IP, each with its own counter, so students who
        // share one campus IP address do not lock each other out.
        $byEmail = fn (Request $request) => mb_strtolower((string) $request->input('email')).'|'.$request->ip();

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by('login|'.$byEmail($request)));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(6)->by('reset|'.$byEmail($request)));
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(30)->by('register|'.$request->ip()));
    }
}
