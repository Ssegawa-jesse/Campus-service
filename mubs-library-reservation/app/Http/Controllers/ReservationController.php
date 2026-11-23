<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservationController extends Controller
{
    /**
     * Display the list of reservations already made.
     */
    public function index(): View
    {
        $reservations = Reservation::orderBy('pickup_date')->latest()->get();

        return view('reservations.index', compact('reservations'));
    }

    /**
     * Show the form for making a new reservation.
     */
    public function create(): View
    {
        return view('reservations.create', [
            'reservation' => new Reservation,
            'programmes' => Reservation::PROGRAMMES,
        ]);
    }

    /**
     * Validate the submitted form and save the reservation to the database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateReservation($request, isNew: true);

        Reservation::create($validated);

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Reservation saved. It now appears in the list below.');
    }

    /**
     * Show the form for editing an existing reservation.
     */
    public function edit(Reservation $reservation): View
    {
        return view('reservations.edit', [
            'reservation' => $reservation,
            'programmes' => Reservation::PROGRAMMES,
        ]);
    }

    /**
     * Validate the changes and update the reservation in the database.
     */
    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $validated = $this->validateReservation($request, isNew: false);

        $reservation->update($validated);

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Reservation for "'.$reservation->book_title.'" updated.');
    }

    /**
     * Remove a reservation from the database.
     */
    public function destroy(Reservation $reservation): RedirectResponse
    {
        $reservation->delete();

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Reservation deleted.');
    }

    /**
     * Validation rules shared by store and update.
     * A new reservation cannot be for a date in the past.
     *
     * @return array<string, mixed>
     */
    private function validateReservation(Request $request, bool $isNew): array
    {
        return $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:50'],
            'programme' => ['required', Rule::in(Reservation::PROGRAMMES)],
            'book_title' => ['required', 'string', 'max:255'],
            'pickup_date' => $isNew
                ? ['required', 'date', 'after_or_equal:today']
                : ['required', 'date'],
        ], [
            'pickup_date.after_or_equal' => 'The pickup date cannot be in the past.',
        ]);
    }
}
