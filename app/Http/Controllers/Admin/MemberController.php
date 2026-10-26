<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->query('q');

        return view('admin.members.index', [
            'members' => Member::with('user')
                ->withCount('activeLoans')
                ->when($term, fn ($q) => $q->where(fn ($q) => $q->where('member_no', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%"))))
                ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.members.form', ['member' => new Member(['type' => 'student', 'expiry_date' => now()->addYear()]), 'user' => new User]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $password = $data['password'] ?? Str::password(10, symbols: false);

        $member = DB::transaction(function () use ($data, $password) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $password,
                'role' => 'member',
            ]);

            return $user->member()->create([
                'member_no' => $data['member_no'] ?: Member::generateNumber($data['type']),
                'type' => $data['type'],
                'programme' => $data['programme'],
                'max_loans' => $data['max_loans'],
                'expiry_date' => $data['expiry_date'],
            ]);
        });

        AuditLog::record('member.created', "Registered {$member->member_no} ({$member->user->name})");

        return redirect()->route('admin.members.show', $member)
            ->with('success', "Member registered. Login password: {$password} — share it with the member.");
    }

    public function show(Member $member)
    {
        $member->load('user');

        return view('admin.members.show', [
            'member' => $member,
            'loans' => $member->loans()->with('copy.book')->latest('issued_at')->paginate(10),
            'reservations' => $member->reservations()->with('book')->whereIn('status', ['waiting', 'ready'])->get(),
            'fines' => $member->fines()->with('loan.copy.book')->latest()->get(),
            'blocks' => $member->borrowingBlocks(),
        ]);
    }

    public function edit(Member $member)
    {
        return view('admin.members.form', ['member' => $member, 'user' => $member->user]);
    }

    public function update(Request $request, Member $member)
    {
        $data = $this->validated($request, $member);

        DB::transaction(function () use ($member, $data) {
            $member->user->update(array_filter([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'] ?: null,
            ]));
            $member->update([
                'member_no' => $data['member_no'] ?: $member->member_no,
                'type' => $data['type'],
                'programme' => $data['programme'],
                'max_loans' => $data['max_loans'],
                'expiry_date' => $data['expiry_date'],
            ]);
        });

        AuditLog::record('member.updated', "Updated {$member->member_no}");

        return redirect()->route('admin.members.show', $member)->with('success', 'Member updated.');
    }

    public function card(Member $member)
    {
        return view('members.card', ['member' => $member->load('user')]);
    }

    public function toggleStatus(Member $member)
    {
        $user = $member->user;
        $user->update(['status' => $user->isActive() ? 'suspended' : 'active']);
        AuditLog::record('member.status', "{$member->member_no} is now {$user->status}");

        return back()->with('success', "Account is now {$user->status}.");
    }

    protected function validated(Request $request, ?Member $member = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($member?->user_id)],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{9,15}$/'],
            'member_no' => ['nullable', 'string', 'max:30', Rule::unique('members')->ignore($member?->id)],
            'type' => ['required', Rule::in(['student', 'staff'])],
            'programme' => ['nullable', 'string', 'max:255'],
            'max_loans' => ['nullable', 'integer', 'min:1', 'max:50'],
            'expiry_date' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        // Optional fields left empty are missing from the validated data.
        return $data + array_fill_keys(['member_no', 'programme', 'max_loans', 'expiry_date', 'password'], null);
    }
}
