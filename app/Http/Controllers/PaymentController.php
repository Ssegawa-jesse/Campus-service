<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function checkout(Booking $booking)
    {
        $this->authorizeOwner($booking);

        if (! $booking->canBePaid()) {
            return redirect()->route('tourist.bookings.show', $booking)->with('error', 'This booking cannot be paid right now.');
        }

        return view('payments.checkout', ['booking' => $booking->load('park', 'operator', 'package')]);
    }

    public function pay(Request $request, Booking $booking)
    {
        $this->authorizeOwner($booking);
        abort_unless($booking->canBePaid(), 422, 'This booking cannot be paid right now.');

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'phone' => ['required_unless:method,card', 'nullable', 'regex:/^\+?[0-9 ]{9,15}$/'],
        ]);

        try {
            [, $redirect] = $this->payments->start($booking, $data['method'], $data['phone'] ?? null);
        } catch (\Throwable $e) {
            Log::error('Payment start failed: '.$e->getMessage());

            return back()->with('error', 'We could not start the payment. Please try again or choose another method.');
        }

        return redirect()->away($redirect);
    }

    /** Offline simulator: a mock "approve on your phone" screen. */
    public function simulate(string $txRef)
    {
        $payment = Payment::where('tx_ref', $txRef)->where('gateway', 'simulate')->firstOrFail();
        abort_unless($payment->user_id === auth()->id(), 403);

        return view('payments.simulate', ['payment' => $payment->load('booking.park')]);
    }

    public function simulateComplete(Request $request, string $txRef)
    {
        $payment = Payment::where('tx_ref', $txRef)->where('gateway', 'simulate')->firstOrFail();
        abort_unless($payment->user_id === auth()->id(), 403);

        $request->validate(['pin' => $request->input('decision') === 'approve' ? 'required|digits_between:4,5' : 'nullable']);

        $this->payments->complete($payment, ['status' => $request->input('decision') === 'approve' ? 'successful' : 'failed']);

        return $this->finish($payment->fresh());
    }

    /** Flutterwave redirects the customer here after checkout. */
    public function callback(Request $request)
    {
        $payment = Payment::where('tx_ref', $request->query('tx_ref'))->firstOrFail();

        if ($request->query('status') === 'cancelled') {
            $payment->update(['status' => 'failed']);
        } else {
            $this->payments->complete($payment, $request->query());
        }

        return $this->finish($payment->fresh());
    }

    /** Server-to-server notification from Flutterwave (set this URL in the Flutterwave dashboard). */
    public function webhook(Request $request)
    {
        $hash = config('pearl360.payments.flutterwave.secret_hash');
        if (! $hash || ! hash_equals($hash, (string) $request->header('verif-hash'))) {
            abort(401);
        }

        $payment = Payment::where('tx_ref', $request->input('data.tx_ref'))->first();
        if ($payment) {
            $this->payments->complete($payment, $request->input('data', []));
        }

        return response()->json(['received' => true]);
    }

    private function finish(Payment $payment)
    {
        $route = route('tourist.bookings.show', $payment->booking);

        return match ($payment->status) {
            'successful' => redirect($route)->with('success', 'Payment of '.ugx($payment->amount).' received. A confirmation SMS and email have been sent.'),
            'pending' => redirect($route)->with('info', 'Your payment is still processing. We will notify you once it is confirmed.'),
            default => redirect($route)->with('error', 'The payment was not completed. You can try again.'),
        };
    }

    private function authorizeOwner(Booking $booking): void
    {
        abort_unless($booking->tourist_id === auth()->id(), 403);
    }
}
