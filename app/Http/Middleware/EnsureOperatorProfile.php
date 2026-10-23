<?php

namespace App\Http\Middleware;

use App\Models\Operator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every operator account needs a company profile (operators table) – without one it could
 * not be approved or receive bookings. Create a pending profile if it is missing.
 */
class EnsureOperatorProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isOperator() && ! $user->operator) {
            Operator::create([
                'user_id' => $user->id,
                'company_name' => $user->name,
                'slug' => Str::slug($user->name).'-'.Str::lower(Str::random(4)),
                'contact_phone' => $user->phone ?: '',
                'contact_email' => $user->email,
                'status' => 'pending',
            ]);
            $user->load('operator');
        }

        return $next($request);
    }
}
