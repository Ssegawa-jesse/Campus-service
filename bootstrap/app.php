<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'operator.profile' => \App\Http\Middleware\EnsureOperatorProfile::class,
        ]);

        // Payment gateways post back to us without a CSRF token.
        $middleware->validateCsrfTokens(except: ['payments/webhook']);

        // Already logged in and opening /login or /register? Explain instead of silently redirecting –
        // e.g. an admin testing operator sign-up must log out (or use a private window) first.
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->routeIs('login', 'register', 'password.*')) {
                session()->flash('info', 'You are already logged in as '.$request->user()->name.' ('.$request->user()->role.'). '
                    .'To register or log in as someone else, log out first or open a private/incognito window.');
            }

            return route($request->user()->dashboardRoute());
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
