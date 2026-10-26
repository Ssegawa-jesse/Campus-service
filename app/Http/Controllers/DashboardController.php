<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookSuggestion;
use App\Models\Copy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Reservation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return view('admin.dashboard', [
                'stats' => [
                    'titles' => Book::count(),
                    'copies' => Copy::count(),
                    'on_loan' => Copy::where('status', 'on_loan')->count(),
                    'members' => Member::count(),
                    'overdue' => Loan::overdue()->count(),
                    'reservations' => Reservation::whereIn('status', ['waiting', 'ready'])->count(),
                    'unpaid_fines' => (int) Fine::where('status', 'unpaid')->sum('amount'),
                    'suggestions' => BookSuggestion::where('status', 'pending')->count(),
                ],
                'overdueLoans' => Loan::overdue()->with('member.user', 'copy.book')->orderBy('due_at')->take(8)->get(),
                'recentLoans' => Loan::with('member.user', 'copy.book')->latest('issued_at')->take(8)->get(),
            ]);
        }

        $member = $user->member;
        abort_unless($member, 403, 'No library membership is linked to this account.');

        return view('member.dashboard', [
            'member' => $member,
            'loans' => $member->activeLoans()->with('copy.book')->orderBy('due_at')->get(),
            'reservations' => $member->reservations()->whereIn('status', ['waiting', 'ready'])->with('book')->get(),
            'owed' => $member->outstandingFines(),
            'blocks' => $member->borrowingBlocks(),
            'suggestions' => $user->suggestions()->latest()->take(5)->get(),
        ]);
    }
}
