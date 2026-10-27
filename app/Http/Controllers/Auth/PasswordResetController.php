<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * "Forgot password": emails a reset link, then lets the user choose a new password.
 */
class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => __($status)]);
        }

        $response = back()->with('success', 'If that email is registered, we have sent a password reset link to it. Check your inbox.');

        // On a local demo without a mail server, emails only go to the log file,
        // so show the link on screen instead. Never happens in production.
        if ($status === Password::RESET_LINK_SENT && app()->isLocal() && in_array(config('mail.default'), ['log', 'array'], true)) {
            $user = User::where('email', $request->input('email'))->first();
            $response->with('demo_reset_link', route('password.reset', [
                'token' => Password::createToken($user),
                'email' => $user->email,
            ]));
        }

        return $response;
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->setRememberToken(null);
                $user->save();
                event(new PasswordReset($user));
                AuditLog::create(['user_id' => $user->id, 'action' => 'user.password_reset', 'details' => "{$user->email} reset their password", 'ip_address' => request()->ip()]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect()->route('login')->with('success', 'Your password has been changed. You can now log in.');
    }
}
