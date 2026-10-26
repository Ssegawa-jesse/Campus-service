<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Copy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const REPORTS = [
        'loans' => 'Loans issued',
        'overdue' => 'Overdue items',
        'fines' => 'Fines collected',
        'popular' => 'Most borrowed titles',
        'members' => 'Member activity',
    ];

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());
        $loansPerMonth = $months->map(fn (Carbon $m) => Loan::whereBetween('issued_at', [$m, $m->copy()->endOfMonth()])->count());
        $finesPerMonth = $months->map(fn (Carbon $m) => (int) Payment::where('status', 'successful')
            ->whereBetween('paid_at', [$m, $m->copy()->endOfMonth()])->sum('amount'));

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'reports' => self::REPORTS,
            'summary' => [
                'loans' => Loan::whereBetween('issued_at', [$from, $to])->count(),
                'returns' => Loan::whereBetween('returned_at', [$from, $to])->count(),
                'overdue' => Loan::overdue()->count(),
                'collected' => (int) Payment::where('status', 'successful')->whereBetween('paid_at', [$from, $to])->sum('amount'),
                'new_members' => Member::whereBetween('created_at', [$from, $to])->count(),
                'utilisation' => Copy::count() ? round(Copy::where('status', 'on_loan')->count() / Copy::count() * 100, 1) : 0,
            ],
            'chart' => [
                'labels' => $months->map(fn ($m) => $m->format('M Y')),
                'loans' => $loansPerMonth,
                'fines' => $finesPerMonth,
            ],
            'popular' => $this->rows('popular', $from, $to)->take(10),
            'byCategory' => Book::query()
                ->join('copies', 'copies.book_id', '=', 'books.id')
                ->join('loans', 'loans.copy_id', '=', 'copies.id')
                ->leftJoin('categories', 'categories.id', '=', 'books.category_id')
                ->whereBetween('loans.issued_at', [$from, $to])
                ->groupBy('categories.name')
                ->select(DB::raw("coalesce(categories.name, 'Uncategorised') as name"), DB::raw('count(*) as total'))
                ->orderByDesc('total')
                ->get(),
        ]);
    }

    public function export(Request $request, string $report): StreamedResponse
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        [$from, $to] = $this->period($request);
        $rows = $this->rows($report, $from, $to);
        $filename = "{$report}-report-{$from->format('Ymd')}-{$to->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows names correctly
            if ($rows->isNotEmpty()) {
                fputcsv($out, array_keys($rows->first()));
            }
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function print(Request $request, string $report)
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        [$from, $to] = $this->period($request);

        return view('admin.reports.print', [
            'title' => self::REPORTS[$report],
            'from' => $from,
            'to' => $to,
            'rows' => $this->rows($report, $from, $to),
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function period(Request $request): array
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);

        return [
            $request->date('from')?->startOfDay() ?? now()->subDays(30)->startOfDay(),
            $request->date('to')?->endOfDay() ?? now()->endOfDay(),
        ];
    }

    protected function rows(string $report, Carbon $from, Carbon $to)
    {
        return match ($report) {
            'loans' => Loan::with('member.user', 'copy.book')->whereBetween('issued_at', [$from, $to])->orderBy('issued_at')->get()
                ->map(fn (Loan $l) => [
                    'Issued' => $l->issued_at->format('Y-m-d'),
                    'Member No' => $l->member->member_no,
                    'Member' => $l->member->user->name,
                    'Title' => $l->copy->book->title,
                    'Copy' => $l->copy->code,
                    'Due' => $l->due_at->format('Y-m-d'),
                    'Returned' => $l->returned_at?->format('Y-m-d') ?? '',
                ]),
            'overdue' => Loan::overdue()->with('member.user', 'copy.book')->orderBy('due_at')->get()
                ->map(fn (Loan $l) => [
                    'Member No' => $l->member->member_no,
                    'Member' => $l->member->user->name,
                    'Phone' => $l->member->user->phone,
                    'Title' => $l->copy->book->title,
                    'Copy' => $l->copy->code,
                    'Due' => $l->due_at->format('Y-m-d'),
                    'Days overdue' => $l->daysOverdue(),
                    'Fine (UGX)' => $l->calculateFine(),
                ]),
            'fines' => Payment::with('member.user')->where('status', 'successful')->whereBetween('paid_at', [$from, $to])->orderBy('paid_at')->get()
                ->map(fn (Payment $p) => [
                    'Date' => $p->paid_at->format('Y-m-d H:i'),
                    'Reference' => $p->reference,
                    'Member No' => $p->member->member_no,
                    'Member' => $p->member->user->name,
                    'Method' => $p->methodLabel(),
                    'Amount (UGX)' => $p->amount,
                ]),
            'popular' => Book::query()
                ->join('copies', 'copies.book_id', '=', 'books.id')
                ->join('loans', 'loans.copy_id', '=', 'copies.id')
                ->whereBetween('loans.issued_at', [$from, $to])
                ->groupBy('books.id', 'books.title', 'books.isbn')
                ->select('books.title', 'books.isbn', DB::raw('count(loans.id) as times_borrowed'))
                ->orderByDesc('times_borrowed')
                ->get()
                ->map(fn ($b) => ['Title' => $b->title, 'ISBN' => $b->isbn, 'Times borrowed' => (int) $b->times_borrowed]),
            'members' => Member::with('user')
                ->withCount(['loans' => fn ($q) => $q->whereBetween('issued_at', [$from, $to])])
                ->orderByDesc('loans_count')->get()
                ->map(fn (Member $m) => [
                    'Member No' => $m->member_no,
                    'Name' => $m->user->name,
                    'Type' => ucfirst($m->type),
                    'Loans in period' => $m->loans_count,
                    'Unpaid fines (UGX)' => (int) Fine::where('member_id', $m->id)->where('status', 'unpaid')->sum('amount'),
                ]),
        };
    }
}
