<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Tour operator approvals: every new operator registration lands here as "pending". */
class OperatorController extends Controller
{
    public function index(Request $request)
    {
        $this->repairMissingProfiles();

        $status = $request->input('status', Operator::pending()->exists() ? 'pending' : 'all');
        $query = Operator::with('user', 'parks', 'approver')->withCount(['packages', 'bookings'])->latest();
        if (in_array($status, array_keys(Operator::STATUSES), true)) {
            $query->where('status', $status);
        }

        return view('admin.operators.index', [
            'operators' => $query->get(),
            'status' => $status,
            'counts' => Operator::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function approve(Request $request, Operator $operator, NotificationService $notifier)
    {
        $operator->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'rejection_reason' => null,
        ]);
        $notifier->operatorVerified($operator);

        return back()->with('success', "{$operator->company_name} has been approved and notified by SMS and email.");
    }

    public function reject(Request $request, Operator $operator, NotificationService $notifier)
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        $operator->update([
            'status' => 'rejected',
            'approved_at' => null,
            'approved_by' => $request->user()->id,
            'rejection_reason' => $data['reason'] ?? null,
        ]);
        $notifier->operatorRejected($operator);

        return back()->with('success', "{$operator->company_name} has been rejected and notified.");
    }

    /** Put an approved/rejected operator back to "pending" (e.g. while re-checking their licence). */
    public function revoke(Operator $operator)
    {
        $operator->update(['status' => 'pending', 'approved_at' => null]);

        return back()->with('success', "{$operator->company_name} moved back to pending. Their packages are hidden until approved again.");
    }

    /** Safety net: an operator account without a company profile would otherwise never appear for approval. */
    private function repairMissingProfiles(): void
    {
        User::where('role', User::ROLE_OPERATOR)->doesntHave('operator')->get()->each(function (User $user) {
            Operator::create([
                'user_id' => $user->id,
                'company_name' => $user->name,
                'slug' => Str::slug($user->name).'-'.Str::lower(Str::random(4)),
                'contact_phone' => $user->phone ?: '',
                'contact_email' => $user->email,
                'status' => 'pending',
            ]);
        });
    }
}
