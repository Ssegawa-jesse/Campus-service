<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Role-based access control: ->middleware('role:admin') or 'role:operator,admin'. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => 'Your account has been suspended. Contact the administrator.']);
        }

        abort_unless(in_array($user->role, $roles, true), 403, 'You do not have access to this area.');

        return $next($request);
    }
}
