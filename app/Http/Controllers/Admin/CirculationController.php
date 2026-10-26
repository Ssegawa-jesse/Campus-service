<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\Member;
use App\Services\CirculationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Counter operations: issue and return books by scanning QR codes (or typing the codes).
 */
class CirculationController extends Controller
{
    public function __construct(protected CirculationService $circulation) {}

    public function index()
    {
        return view('admin.circulation.index', [
            'recent' => Loan::with('member.user', 'copy.book')->latest('updated_at')->take(10)->get(),
        ]);
    }

    public function issue(Request $request)
    {
        $data = $request->validate([
            'member_no' => ['required', 'string'],
            'copy_code' => ['required', 'string'],
        ]);

        $member = $this->findMember($data['member_no']);
        $copy = $this->findCopy($data['copy_code']);
        $loan = $this->circulation->issue($member, $copy, $request->user()->id);

        return back()->with('success', "Issued \"{$copy->book->title}\" ({$copy->code}) to {$member->user->name}. Due {$loan->due_at->format('D d M Y')}.");
    }

    public function return(Request $request)
    {
        $data = $request->validate(['copy_code' => ['required', 'string']]);

        $copy = $this->findCopy($data['copy_code']);
        $result = $this->circulation->receive($copy, $request->user()->id);

        $message = "Received \"{$copy->book->title}\" ({$copy->code}) from {$result['loan']->member->user->name}.";
        if ($result['fine']) {
            $message .= ' Overdue fine: UGX '.number_format($result['fine']->amount).'.';
        }
        if ($result['reservation']) {
            $message .= " Place this copy on the hold shelf for {$result['reservation']->member->user->name} ({$result['reservation']->member->member_no}).";
        }

        return back()->with('success', $message);
    }

    /** JSON lookup used by the scanner page to preview a member or copy. */
    public function lookup(Request $request)
    {
        $code = trim((string) $request->query('code'));

        if ($member = Member::with('user')->where('member_no', $code)->first()) {
            return response()->json([
                'kind' => 'member',
                'name' => $member->user->name,
                'member_no' => $member->member_no,
                'type' => $member->type,
                'loans' => $member->activeLoans()->count().' / '.$member->loanLimit(),
                'fines' => $member->outstandingFines(),
                'blocks' => $member->borrowingBlocks(),
            ]);
        }

        if ($copy = Copy::with('book', 'currentLoan.member.user')->where('code', $code)->first()) {
            return response()->json([
                'kind' => 'copy',
                'code' => $copy->code,
                'title' => $copy->book->title,
                'status' => $copy->status,
                'borrower' => $copy->currentLoan?->member->user->name,
                'due' => $copy->currentLoan?->due_at->format('d M Y'),
            ]);
        }

        return response()->json(['kind' => 'unknown'], 404);
    }

    public function loans(Request $request)
    {
        $loans = Loan::with('member.user', 'copy.book', 'fine')
            ->when($request->query('status', 'active') === 'active', fn ($q) => $q->active())
            ->when($request->query('status') === 'overdue', fn ($q) => $q->overdue())
            ->when($request->query('status') === 'returned', fn ($q) => $q->whereNotNull('returned_at'))
            ->when($request->query('q'), function ($q, $term) {
                $q->where(fn ($q) => $q->whereHas('member', fn ($m) => $m->where('member_no', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")))
                    ->orWhereHas('copy', fn ($c) => $c->where('code', 'like', "%{$term}%")
                        ->orWhereHas('book', fn ($b) => $b->where('title', 'like', "%{$term}%"))));
            })
            ->orderBy($request->query('status') === 'returned' ? 'returned_at' : 'due_at', $request->query('status') === 'returned' ? 'desc' : 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.circulation.loans', compact('loans'));
    }

    public function renew(Loan $loan)
    {
        $this->circulation->renew($loan);

        return back()->with('success', "Loan renewed until {$loan->due_at->format('d M Y')}.");
    }

    protected function findMember(string $number): Member
    {
        return Member::with('user')->where('member_no', trim($number))->first()
            ?? throw ValidationException::withMessages(['member_no' => "No member found with number \"{$number}\"."]);
    }

    protected function findCopy(string $code): Copy
    {
        return Copy::with('book')->where('code', trim($code))->first()
            ?? throw ValidationException::withMessages(['copy_code' => "No copy found with code \"{$code}\"."]);
    }
}
