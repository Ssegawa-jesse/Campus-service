<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\CirculationService;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $member = $request->user()->member;

        return view('member.loans', [
            'active' => $member->activeLoans()->with('copy.book')->orderBy('due_at')->get(),
            'history' => $member->loans()->whereNotNull('returned_at')->with('copy.book', 'fine')->latest('returned_at')->paginate(10),
        ]);
    }

    public function renew(Request $request, Loan $loan, CirculationService $circulation)
    {
        abort_unless($loan->member_id === $request->user()->member->id, 403);

        $circulation->renew($loan);

        return back()->with('success', "Renewed. New due date: {$loan->due_at->format('d M Y')}.");
    }

    public function card(Request $request)
    {
        return view('members.card', ['member' => $request->user()->member->load('user')]);
    }
}
