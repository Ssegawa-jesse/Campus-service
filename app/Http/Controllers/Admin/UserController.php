<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->latest();
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }
        if ($q = $request->input('q')) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"));
        }

        return view('admin.users.index', ['users' => $query->paginate(20)->withQueryString()]);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users',
            'phone' => ['required', 'regex:/^\+?[0-9 ]{9,15}$/'],
            'role' => ['required', Rule::in(['admin', 'tourist', 'operator'])],
            'password' => ['required', Password::min(8)],
            'company_name' => 'required_if:role,operator|nullable|string|max:150',
        ]);

        $user = User::create(collect($data)->except('company_name')->all() + ['country' => 'Uganda']);

        if ($user->isOperator()) {
            Operator::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'slug' => Str::slug($data['company_name']).'-'.Str::lower(Str::random(4)),
                'contact_phone' => $data['phone'],
                'contact_email' => $data['email'],
                'is_verified' => true,
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function toggle(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot suspend yourself.');
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->name.($user->is_active ? ' re-activated.' : ' suspended.'));
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot delete yourself.');
        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
