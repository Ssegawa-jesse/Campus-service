<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        return view('admin.staff.index', ['staff' => User::whereIn('role', ['admin', 'librarian'])->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.staff.form', ['staff' => new User(['role' => 'librarian'])]);
    }

    public function store(Request $request)
    {
        $user = User::create($this->validated($request) + ['status' => 'active']);
        AuditLog::record('staff.created', "Created {$user->role} account for {$user->name}");

        return redirect()->route('admin.staff.index')->with('success', 'Staff account created.');
    }

    public function edit(User $staff)
    {
        abort_unless($staff->isStaff(), 404);

        return view('admin.staff.form', compact('staff'));
    }

    public function update(Request $request, User $staff)
    {
        abort_unless($staff->isStaff(), 404);
        $data = $this->validated($request, $staff);

        if ($staff->is($request->user()) && ($data['role'] !== 'admin' || $data['status'] !== 'active')) {
            return back()->withErrors(['role' => 'You cannot demote or suspend your own account.']);
        }

        $staff->update(array_filter($data, fn ($v) => $v !== null));
        AuditLog::record('staff.updated', "Updated {$staff->name} ({$staff->role}, {$staff->status})");

        return redirect()->route('admin.staff.index')->with('success', 'Staff account updated.');
    }

    public function destroy(Request $request, User $staff)
    {
        abort_unless($staff->isStaff(), 404);
        abort_if($staff->is($request->user()), 422, 'You cannot delete your own account.');

        AuditLog::record('staff.deleted', "Deleted {$staff->role} account {$staff->name}");
        $staff->delete();

        return back()->with('success', 'Staff account deleted.');
    }

    protected function validated(Request $request, ?User $staff = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($staff?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['admin', 'librarian'])],
            'status' => [$staff ? 'required' : 'nullable', Rule::in(['active', 'suspended'])],
            'password' => [$staff ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
    }
}
