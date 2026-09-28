<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Operator;
use App\Models\Park;
use App\Models\TourPackage;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(private NotificationService $notifier)
    {
    }

    public function index(Request $request)
    {
        return view('tourist.bookings.index', [
            'bookings' => $request->user()->bookings()->with('park', 'operator', 'package')->latest()->paginate(10),
        ]);
    }

    public function create(Request $request)
    {
        $package = $request->filled('package') ? TourPackage::with('operator', 'park')->where('slug', $request->input('package'))->first() : null;
        $park = $package?->park ?? ($request->filled('park') ? Park::where('slug', $request->input('park'))->first() : null);

        return view('tourist.bookings.create', [
            'package' => $package,
            'park' => $park,
            'parks' => Park::published()->orderBy('name')->get(),
            'operators' => Operator::where('is_verified', true)->with('parks:id')->orderBy('company_name')->get(),
            'selectedOperator' => $package?->operator_id ?? $request->integer('operator') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'park_id' => ['required', Rule::exists('parks', 'id')->where('is_published', true)],
            'operator_id' => ['required', Rule::exists('operators', 'id')->where('is_verified', true)],
            'tour_package_id' => 'nullable|exists:tour_packages,id',
            'travel_date' => 'required|date|after:today',
            'group_size' => 'required|integer|min:1|max:50',
            'contact_phone' => ['required', 'regex:/^\+?[0-9 ]{9,15}$/'],
            'message' => 'nullable|string|max:2000',
        ]);

        if (! empty($data['tour_package_id'])) {
            $package = TourPackage::findOrFail($data['tour_package_id']);
            abort_unless($package->operator_id == $data['operator_id'], 422, 'Package does not belong to that operator.');
            $data['park_id'] = $package->park_id;
        }

        $booking = Booking::create($data + ['tourist_id' => $request->user()->id, 'status' => 'pending']);
        $this->notifier->bookingCreated($booking);

        return redirect()->route('tourist.bookings.show', $booking)
            ->with('success', 'Your enquiry has been sent! The operator will reply with a quote – you will get an SMS and email.');
    }

    public function show(Request $request, Booking $booking)
    {
        abort_unless($booking->tourist_id === $request->user()->id, 403);

        return view('tourist.bookings.show', [
            'booking' => $booking->load('park', 'operator', 'package', 'payments'),
            'hasReviewed' => $request->user()->reviews()->where('park_id', $booking->park_id)->exists(),
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        abort_unless($booking->tourist_id === $request->user()->id, 403);

        if (! in_array($booking->status, ['pending', 'quoted'], true)) {
            return back()->with('error', 'Paid bookings cannot be cancelled online. Please contact the operator.');
        }

        $booking->update(['status' => 'cancelled']);
        $this->notifier->bookingCancelled($booking);

        return back()->with('success', 'Booking cancelled.');
    }
}
