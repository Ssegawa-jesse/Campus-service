<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Self-registration: anyone (students and staff) can create a member account.
 */
class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{9,15}$/'],
            'type' => ['required', Rule::in(['student', 'staff'])],
            'member_no' => ['nullable', 'string', 'max:30', 'unique:members,member_no'],
            'programme' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'phone.regex' => 'Enter a valid phone number, e.g. 0772123456.',
            'member_no.unique' => 'This student/staff number is already registered.',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => 'member',
            ]);

            $user->member()->create([
                'member_no' => ($data['member_no'] ?? null) ?: Member::generateNumber($data['type']),
                'type' => $data['type'],
                'programme' => $data['programme'] ?? null,
                'expiry_date' => now()->addYear(),
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('member.registered', "{$user->name} registered as {$user->member->member_no}");

        return redirect()->route('dashboard')->with('success', "Welcome, {$user->name}! Your library number is {$user->member->member_no}.");
    }
}
