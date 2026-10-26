<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Reservation;
use App\Services\CirculationService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        return view('admin.reservations.index', [
            'reservations' => Reservation::with('book', 'member.user', 'copy')
                ->when($status === 'open', fn ($q) => $q->whereIn('status', ['waiting', 'ready']))
                ->when($status !== 'open' && $status !== 'all', fn ($q) => $q->where('status', $status))
                ->orderByRaw("case status when 'ready' then 0 when 'waiting' then 1 else 2 end")
                ->oldest()
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function destroy(Reservation $reservation, CirculationService $circulation)
    {
        abort_unless($reservation->isOpen(), 422, 'This reservation is already closed.');

        $circulation->cancelReservation($reservation);
        AuditLog::record('reservation.cancelled', "Cancelled reservation #{$reservation->id} for \"{$reservation->book->title}\"");

        return back()->with('success', 'Reservation cancelled.');
    }
}
