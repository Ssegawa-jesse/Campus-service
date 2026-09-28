<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Services\NotificationService;

class OperatorController extends Controller
{
    public function index()
    {
        return view('admin.operators.index', [
            'operators' => Operator::with('user', 'parks')->withCount(['packages', 'bookings'])->orderBy('is_verified')->latest()->get(),
        ]);
    }

    public function toggleVerify(Operator $operator, NotificationService $notifier)
    {
        $operator->update(['is_verified' => ! $operator->is_verified]);

        if ($operator->is_verified) {
            $notifier->operatorVerified($operator);
        }

        return back()->with('success', $operator->company_name.($operator->is_verified ? ' verified and notified.' : ' un-verified.'));
    }
}
