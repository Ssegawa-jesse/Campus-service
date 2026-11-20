<?php

namespace App\Http\Controllers;

use App\Models\LoginActivity;
use App\Models\Operator;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Registration, login, logout, profile and password reset.
 * Accounts are saved in the `users` table (passwords as bcrypt hashes, never plain text);
 * every registration, login, failed login and logout is written to `login_activities`.
 */
class AuthController extends Controller
{
    public const PHONE_RULE = 'regex:/^\+?[0-9 ()-]{9,20}$/';

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            LoginActivity::record('failed', User::where('email', $credentials['email'])->first(), $credentials['email']);

            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $user = $request->user();
        if (! $user->is_active) {
            LoginActivity::record('blocked', $user);
            Auth::logout();

            return back()->withErrors(['email' => 'Your account has been suspended. Contact the administrator.']);
        }

        $request->session()->regenerate();
        LoginActivity::record('login', $user);

        return redirect()->intended(route($user->dashboardRoute()))
            ->with('success', 'Welcome back, '.Str::words($user->name, 1, '').'!');
    }

    public function showRegister(Request $request)
    {
        return view('auth.register', ['role' => $request->input('as') === 'operator' ? 'operator' : 'tourist']);
    }

    public function register(Request $request, NotificationService $notifier)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_TOURIST, User::ROLE_OPERATOR])],
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => ['required', 'string', 'max:20', self::PHONE_RULE],
            'country' => 'nullable|string|max:80',
            'password' => ['required', 'confirmed', Password::min(8)],
            'company_name' => 'required_if:role,operator|nullable|string|max:150',
            'licence_no' => 'nullable|string|max:60',
        ], [
            'company_name.required_if' => 'Please enter your company name to register as a tour operator.',
            'phone.regex' => 'Enter a valid phone number, e.g. 0772123456 or +256772123456.',
            'email.unique' => 'An account with this email already exists. Log in instead, or use "Forgot password".',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'country' => $data['country'] ?? 'Uganda',
                'password' => $data['password'],
                'role' => $data['role'],
            ]);

            if ($user->isOperator()) {
                Operator::create([
                    'user_id' => $user->id,
                    'company_name' => $data['company_name'],
                    'slug' => Str::slug($data['company_name']).'-'.Str::lower(Str::random(4)),
                    'licence_no' => $data['licence_no'] ?? null,
                    'contact_phone' => $data['phone'],
                    'contact_email' => $data['email'],
                    'status' => 'pending',
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        LoginActivity::record('registered', $user);
        $notifier->welcome($user);

        return redirect()->route($user->dashboardRoute())->with('success', $user->isOperator()
            ? 'Your operator account has been created and sent to the administrators for approval. You will get an SMS and email once approved – meanwhile, complete your company profile.'
            : 'Welcome to Pearl360! Your account has been created. Start by planning your trip or taking a virtual tour.');
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            LoginActivity::record('logout', $request->user());
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
    }

    /* ---------------- Forgot / reset password ---------------- */

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $request->validate(['email' => 'required|email']);

        PasswordBroker::sendResetLink($request->only('email'));

        // Same answer whether or not the email exists, so accounts can't be discovered.
        return back()->with('success', 'If an account exists for that email, a password reset link has been sent to it.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $status = PasswordBroker::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
                LoginActivity::record('password_reset', $user);
            }
        );

        return $status === PasswordBroker::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Your password has been reset. You can now log in.')
            : back()->withErrors(['email' => __($status)])->onlyInput('email');
    }

    /* ---------------- Profile ---------------- */

    public function editProfile()
    {
        return view('auth.profile', [
            'user' => auth()->user(),
            'activities' => auth()->user()->loginActivities()->latest('created_at')->take(8)->get(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', self::PHONE_RULE],
            'country' => 'nullable|string|max:80',
            'sms_opt_in' => 'nullable|boolean',
            'avatar' => 'nullable|image|max:2048',
            'current_password' => 'nullable|required_with:password|current_password',
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->storeUpload($request->file('avatar'), 'avatars', $user->avatar);
        }

        $data['sms_opt_in'] = $request->boolean('sms_opt_in');
        if (empty($data['password'])) {
            unset($data['password']);
        }
        unset($data['current_password']);

        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }
}
