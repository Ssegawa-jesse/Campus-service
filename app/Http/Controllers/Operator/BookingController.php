<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /** Which status an operator may move a booking to, from each current status. */
    private const TRANSITIONS = [
        'pending' => ['quoted', 'declined'],
        'quoted' => ['quoted', 'declined'],
        'paid' => ['confirmed'],
        'confirmed' => ['completed'],
    ];

    public function index(Request $request)
    {
        $query = $request->user()->operator->bookings()->with('tourist', 'park', 'package')->latest();
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('operator.bookings.index', ['bookings' => $query->paginate(15)->withQueryString()]);
    }

    public function show(Request $request, Booking $booking)
    {
        $this->own($request, $booking);

        return view('operator.bookings.show', [
            'booking' => $booking->load('tourist', 'park', 'package', 'payments'),
            'allowed' => self::TRANSITIONS[$booking->status] ?? [],
        ]);
    }

    public function update(Request $request, Booking $booking, NotificationService $notifier)
    {
        $this->own($request, $booking);
        $allowed = self::TRANSITIONS[$booking->status] ?? [];

        $data = $request->validate([
            'status' => ['required', Rule::in($allowed)],
            'quoted_amount' => 'required_if:status,quoted|nullable|integer|min:1000',
            'operator_response' => 'nullable|string|max:2000',
        ]);

        $booking->update($data + ['responded_at' => now()]);
        $notifier->bookingUpdated($booking);

        return back()->with('success', 'Booking updated and the tourist has been notified by SMS and email.');
    }

    private function own(Request $request, Booking $booking): void
    {
        abort_unless($booking->operator_id === $request->user()->operator->id, 403);
    }
}
