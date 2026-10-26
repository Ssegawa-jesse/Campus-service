<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Reservation;
use App\Services\CirculationService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        return view('member.reservations', [
            'reservations' => $request->user()->member->reservations()->with('book')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request, Book $book, CirculationService $circulation)
    {
        $reservation = $circulation->reserve($request->user()->member, $book);

        return back()->with('success', "Reserved. You are number {$reservation->position()} in the waiting list — we will SMS you when it is ready.");
    }

    public function destroy(Request $request, Reservation $reservation, CirculationService $circulation)
    {
        abort_unless($reservation->member_id === $request->user()->member->id, 403);
        abort_unless($reservation->isOpen(), 422, 'This reservation is already closed.');

        $circulation->cancelReservation($reservation);

        return back()->with('success', 'Reservation cancelled.');
    }
}
