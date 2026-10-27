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
                'overdueLoans' => Loan::overdue()->with('member.user', 'copy.book.authors')->orderBy('due_at')->take(6)->get(),
                'recentLoans' => Loan::with('member.user', 'copy.book.authors')->latest('issued_at')->take(6)->get(),
                'topBooks' => Book::with('authors')->withCount('loans')->orderByDesc('loans_count')->take(5)->get(),
                'newMembers' => Member::with('user')->latest()->take(5)->get(),
                'activity' => $this->activityChart(),
                'copyStatus' => Copy::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            ]);
        }

        $member = $user->member;
        abort_unless($member, 403, 'No library membership is linked to this account.');

        return view('member.dashboard', [
            'member' => $member,
            'loans' => $member->activeLoans()->with('copy.book.authors')->orderBy('due_at')->get(),
            'reservations' => $member->reservations()->whereIn('status', ['waiting', 'ready'])->with('book.authors')->get(),
            'borrowedTotal' => $member->loans()->count(),
            'forYou' => Book::with('authors')->withCount(['copies', 'availableCopies'])->has('availableCopies')
                ->whereNotIn('id', $member->loans()->join('copies', 'copies.id', '=', 'loans.copy_id')->select('copies.book_id'))
                ->inRandomOrder()->take(6)->get(),
            'owed' => $member->outstandingFines(),
            'blocks' => $member->borrowingBlocks(),
            'suggestions' => $user->suggestions()->latest()->take(5)->get(),
        ]);
    }

    /** Loans issued and returned per day over the last 14 days. */
    protected function activityChart(): array
    {
        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());

        return [
            'labels' => $days->map(fn ($d) => $d->format('d M'))->all(),
            'issued' => $days->map(fn ($d) => Loan::whereBetween('issued_at', [$d, $d->copy()->endOfDay()])->count())->all(),
            'returned' => $days->map(fn ($d) => Loan::whereBetween('returned_at', [$d, $d->copy()->endOfDay()])->count())->all(),
        ];
    }
}
