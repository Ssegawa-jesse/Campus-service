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
        // Instead of a blank "500 Server Error", explain unfinished local setup.
        $exceptions->render(function (\Illuminate\Encryption\MissingAppKeyException $e) {
            $noEnv = ! file_exists(base_path('.env'));

            return response()->view('errors.setup', [
                'problem' => $noEnv
                    ? 'The .env settings file is missing (on Windows it is often saved as ".env.txt" by mistake).'
                    : 'The application key (APP_KEY) in .env is empty.',
                'commands' => ($noEnv ? "copy .env.example .env\n" : '')."php artisan key:generate\nphp artisan config:clear",
            ], 500);
        });
        $exceptions->render(function (\Illuminate\Database\QueryException $e) {
            $msg = $e->getMessage();
            $setup = match (true) {
                str_contains($msg, '2002') || str_contains($msg, 'refused') => ['MySQL is not running.', "Start MySQL in the XAMPP Control Panel\nphp artisan pearl360:doctor --fix"],
                str_contains($msg, '1049') || str_contains($msg, 'Unknown database') => ['The MySQL database named in .env (DB_DATABASE) does not exist yet.', "php artisan pearl360:doctor --fix\nphp artisan migrate --seed"],
                str_contains($msg, '1045') || str_contains($msg, 'Access denied') => ['MySQL rejected the username/password in .env.', "Set DB_USERNAME=root and DB_PASSWORD= (empty) in .env for XAMPP\nphp artisan config:clear"],
                str_contains($msg, 'no such table') || str_contains($msg, "doesn't exist") || str_contains($msg, '1146') => ['The database tables have not been created yet.', 'php artisan migrate --seed'],
                default => null,
            };
            if ($setup) {
                return response()->view('errors.setup', ['problem' => $setup[0], 'commands' => $setup[1]], 500);
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
