<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Copy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Reservation;
use App\Support\LibrarySettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Borrowing rules: issuing, returning and renewing copies, fines and the reservation queue.
 */
class CirculationService
{
    public function __construct(protected NotificationService $notifier) {}

    public function issue(Member $member, Copy $copy, ?int $staffId = null): Loan
    {
        $blocks = $member->borrowingBlocks();
        if ($blocks) {
            throw ValidationException::withMessages(['member' => $blocks]);
        }

        if ($copy->status === 'on_hold') {
            $hold = Reservation::where('copy_id', $copy->id)->where('status', 'ready')->first();
            if ($hold && $hold->member_id !== $member->id) {
                throw ValidationException::withMessages([
                    'copy' => "This copy is on hold for {$hold->member->user->name} ({$hold->member->member_no}).",
                ]);
            }
        } elseif ($copy->status !== 'available') {
            throw ValidationException::withMessages([
                'copy' => 'This copy is not available (status: '.str_replace('_', ' ', $copy->status).').',
            ]);
        }

        if ($member->activeLoans()->whereHas('copy', fn ($q) => $q->where('book_id', $copy->book_id))->exists()) {
            throw ValidationException::withMessages(['copy' => 'The member already has a copy of this title on loan.']);
        }

        return DB::transaction(function () use ($member, $copy, $staffId) {
            $loan = Loan::create([
                'copy_id' => $copy->id,
                'member_id' => $member->id,
                'issued_by' => $staffId,
                'issued_at' => now(),
                'due_at' => now()->addDays($member->loanPeriodDays())->endOfDay(),
            ]);

            $copy->update(['status' => 'on_loan']);

            Reservation::where('member_id', $member->id)
                ->where('book_id', $copy->book_id)
                ->whereIn('status', ['waiting', 'ready'])
                ->update(['status' => 'fulfilled', 'copy_id' => $copy->id]);

            AuditLog::record('loan.issued', "Copy {$copy->code} issued to {$member->member_no}, due {$loan->due_at->format('d M Y')}");

            return $loan;
        });
    }

    /**
     * Receive a copy back. Returns the loan and any fine raised.
     *
     * @return array{loan: Loan, fine: ?Fine, reservation: ?Reservation}
     */
    public function receive(Copy $copy, ?int $staffId = null): array
    {
        $loan = $copy->currentLoan;
        if (! $loan) {
            throw ValidationException::withMessages(['copy' => "Copy {$copy->code} is not on loan."]);
        }

        return DB::transaction(function () use ($loan, $copy, $staffId) {
            $loan->update(['returned_at' => now(), 'received_by' => $staffId]);

            $fine = $this->settleFine($loan);

            $copy->update(['status' => 'available']);
            $reservation = $this->allocateToNextReservation($copy);

            AuditLog::record('loan.returned', "Copy {$copy->code} returned by {$loan->member->member_no}"
                .($fine ? ', fine UGX '.number_format($fine->amount) : ''));

            return ['loan' => $loan, 'fine' => $fine, 'reservation' => $reservation];
        });
    }

    public function renew(Loan $loan): Loan
    {
        if ($loan->returned_at) {
            throw ValidationException::withMessages(['loan' => 'This book has already been returned.']);
        }
        if ($loan->isOverdue()) {
            throw ValidationException::withMessages(['loan' => 'Overdue books cannot be renewed. Please return the book.']);
        }
        if ($loan->renewals >= LibrarySettings::int('max_renewals')) {
            throw ValidationException::withMessages(['loan' => 'This loan has reached the maximum number of renewals.']);
        }
        if ($loan->copy->book->waitingReservations()->exists()) {
            throw ValidationException::withMessages(['loan' => 'Another member has reserved this book, so it cannot be renewed.']);
        }

        $loan->update([
            'due_at' => $loan->due_at->copy()->addDays($loan->member->loanPeriodDays())->endOfDay(),
            'renewals' => $loan->renewals + 1,
            'reminder_sent_at' => null,
        ]);

        AuditLog::record('loan.renewed', "Loan #{$loan->id} renewed until {$loan->due_at->format('d M Y')}");

        return $loan;
    }

    /** Create or update the fine for an overdue loan. */
    public function settleFine(Loan $loan): ?Fine
    {
        $amount = $loan->calculateFine();
        if ($amount === 0) {
            return null;
        }

        $fine = Fine::firstOrNew(['loan_id' => $loan->id], ['member_id' => $loan->member_id, 'status' => 'unpaid']);
        if ($fine->exists && $fine->status !== 'unpaid') {
            return $fine;
        }
        $fine->amount = $amount;
        $fine->save();

        return $fine;
    }

    public function reserve(Member $member, Book $book): Reservation
    {
        if ($book->availableCopies()->exists()) {
            throw ValidationException::withMessages(['book' => 'A copy is available on the shelf — you can borrow it directly at the counter.']);
        }
        if ($book->copies()->count() === 0) {
            throw ValidationException::withMessages(['book' => 'The library has no copies of this title. Please recommend it for purchase instead.']);
        }
        if ($member->reservations()->where('book_id', $book->id)->whereIn('status', ['waiting', 'ready'])->exists()) {
            throw ValidationException::withMessages(['book' => 'You have already reserved this book.']);
        }
        if ($member->activeLoans()->whereHas('copy', fn ($q) => $q->where('book_id', $book->id))->exists()) {
            throw ValidationException::withMessages(['book' => 'You already have this book on loan.']);
        }

        $reservation = $member->reservations()->create(['book_id' => $book->id, 'status' => 'waiting']);
        AuditLog::record('reservation.created', "{$member->member_no} reserved \"{$book->title}\"");

        return $reservation;
    }

    public function cancelReservation(Reservation $reservation): void
    {
        $copy = $reservation->status === 'ready' ? $reservation->copy : null;
        $reservation->update(['status' => 'cancelled']);

        if ($copy && $copy->status === 'on_hold') {
            $copy->update(['status' => 'available']);
            $this->allocateToNextReservation($copy);
        }
    }

    /** Hold a returned copy for the first member in the waiting list and tell them. */
    public function allocateToNextReservation(Copy $copy): ?Reservation
    {
        $next = $copy->book->waitingReservations()->with('member.user')->first();
        if (! $next) {
            return null;
        }

        $days = LibrarySettings::int('reservation_hold_days');
        $next->update([
            'status' => 'ready',
            'copy_id' => $copy->id,
            'notified_at' => now(),
            'expires_at' => now()->addDays($days)->endOfDay(),
        ]);
        $copy->update(['status' => 'on_hold']);

        $title = $copy->book->title;
        $this->notifier->notify(
            $next->member->user,
            'Your reserved book is ready',
            "Hello {$next->member->user->name}, \"{$title}\" that you reserved is now available. "
            ."Collect it from the library counter by {$next->expires_at->format('d M Y')}. - ".LibrarySettings::get('library_name')
        );

        return $next;
    }

    /** Release copies whose holds were not collected in time. */
    public function expireReservations(): int
    {
        $expired = Reservation::where('status', 'ready')->where('expires_at', '<', now())->with('copy')->get();

        foreach ($expired as $reservation) {
            $reservation->update(['status' => 'expired']);
            if ($reservation->copy && $reservation->copy->status === 'on_hold') {
                $reservation->copy->update(['status' => 'available']);
                $this->allocateToNextReservation($reservation->copy);
            }
        }

        return $expired->count();
    }
}
