<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        if (! $request->user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account has been suspended. Contact the administrator.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route($request->user()->dashboardRoute()));
    }

    public function showRegister(Request $request)
    {
        return view('auth.register', ['role' => $request->input('as') === 'operator' ? 'operator' : 'tourist']);
    }

    public function register(Request $request, NotificationService $notifier)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_TOURIST, User::ROLE_OPERATOR])],
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users',
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9 ]{9,15}$/'],
            'country' => 'nullable|string|max:80',
            'password' => ['required', 'confirmed', Password::min(8)],
            'company_name' => 'required_if:role,operator|nullable|string|max:150',
            'licence_no' => 'nullable|string|max:60',
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
                ]);
            }

            return $user;
        });

        $notifier->welcome($user);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->dashboardRoute())->with('success', $user->isOperator()
            ? 'Welcome! Complete your company profile. The UWA admin will verify your account before tourists can book you.'
            : 'Welcome to Pearl360! Start by planning your trip or taking a virtual tour.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function editProfile()
    {
        return view('auth.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9 ]{9,15}$/'],
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
