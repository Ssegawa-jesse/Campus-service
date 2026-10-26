<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Fine;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class FineController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'unpaid');

        return view('admin.fines.index', [
            'fines' => Fine::with('member.user', 'loan.copy.book', 'waiver', 'payments')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->when($request->query('q'), fn ($q, $term) => $q->whereHas('member', fn ($m) => $m->where('member_no', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"))))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'totals' => [
                'unpaid' => (int) Fine::where('status', 'unpaid')->sum('amount'),
                'paid' => (int) Fine::where('status', 'paid')->sum('amount'),
                'waived' => (int) Fine::where('status', 'waived')->sum('amount'),
            ],
        ]);
    }

    public function waive(Request $request, Fine $fine)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        abort_unless($fine->status === 'unpaid', 422, 'Only unpaid fines can be waived.');

        $fine->update(['status' => 'waived', 'waived_by' => $request->user()->id, 'reason' => $data['reason']]);
        AuditLog::record('fine.waived', "Waived fine #{$fine->id} (UGX ".number_format($fine->amount)."): {$data['reason']}");

        return back()->with('success', 'Fine waived.');
    }

    public function cash(Request $request, Fine $fine, PaymentService $payments)
    {
        abort_unless($fine->status === 'unpaid', 422, 'This fine is already settled.');

        $payment = $payments->start($fine, 'cash', null);
        $payments->markSuccessful($payment, null, $request->user()->id);

        return back()->with('success', "Cash payment recorded. Receipt {$payment->reference}.");
    }
}
