<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with('tourist', 'operator', 'park')->latest();
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('admin.bookings.index', ['bookings' => $query->paginate(20)->withQueryString()]);
    }

    public function payments(Request $request)
    {
        $query = Payment::with('booking.park', 'user')->latest();
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('admin.bookings.payments', [
            'payments' => $query->paginate(20)->withQueryString(),
            'total' => (int) Payment::where('status', 'successful')->sum('amount'),
        ]);
    }
}
