<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** User management. The system supports any number of administrators (UWA staff). */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('operator')->latest();
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }
        if ($q = $request->input('q')) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"));
        }

        return view('admin.users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'counts' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
        ]);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users',
            'phone' => ['required', AuthController::PHONE_RULE],
            'role' => ['required', Rule::in(['admin', 'tourist', 'operator'])],
            'password' => ['required', Password::min(8)],
            'company_name' => 'required_if:role,operator|nullable|string|max:150',
        ]);

        $user = User::create(collect($data)->except('company_name')->all() + ['country' => 'Uganda']);

        if ($user->isOperator()) {
            $this->ensureOperatorProfile($user, $data['company_name'], approvedBy: $request->user());
        }

        return redirect()->route('admin.users.index')->with('success', ucfirst($user->role)." account for {$user->name} created.");
    }

    /** Change a user's role: promote to admin, make operator, or demote. */
    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', Rule::in(['admin', 'tourist', 'operator'])]]);

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own admin rights. Ask another administrator.');
        }
        if ($user->isLastAdmin() && $data['role'] !== 'admin') {
            return back()->with('error', 'This is the last administrator. Promote someone else first.');
        }

        $user->update(['role' => $data['role']]);
        if ($user->isOperator()) {
            $this->ensureOperatorProfile($user, $user->name, approvedBy: $request->user());
        }

        return back()->with('success', "{$user->name} is now ".($data['role'] === 'admin' ? 'an administrator' : 'a '.$data['role']).'.');
    }

    /** Set a new temporary password (useful when email is not configured). */
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate(['password' => ['required', Password::min(8)]]);
        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        LoginActivity::record('password_reset', $user);

        return back()->with('success', "Password for {$user->name} changed. Share it with them privately.");
    }

    public function toggle(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot suspend yourself.');
        }
        if ($user->is_active && $user->isLastAdmin()) {
            return back()->with('error', 'You cannot suspend the last administrator.');
        }
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->name.($user->is_active ? ' re-activated.' : ' suspended.'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete yourself.');
        }
        if ($user->isLastAdmin()) {
            return back()->with('error', 'You cannot delete the last administrator.');
        }
        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    public function activity(Request $request)
    {
        $query = LoginActivity::with('user')->latest('created_at');
        if ($event = $request->input('event')) {
            $query->where('event', $event);
        }
        if ($q = $request->input('q')) {
            $query->where('email', 'like', "%{$q}%");
        }

        return view('admin.users.activity', ['activities' => $query->paginate(30)->withQueryString()]);
    }

    private function ensureOperatorProfile(User $user, string $company, User $approvedBy): void
    {
        if ($user->operator) {
            return;
        }
        Operator::create([
            'user_id' => $user->id,
            'company_name' => $company,
            'slug' => Str::slug($company).'-'.Str::lower(Str::random(4)),
            'contact_phone' => $user->phone ?: '',
            'contact_email' => $user->email,
            'status' => 'approved', // created by an admin, so already trusted
            'approved_at' => now(),
            'approved_by' => $approvedBy->id,
        ]);
    }
}
