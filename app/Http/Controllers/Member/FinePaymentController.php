<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Fine;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class FinePaymentController extends Controller
{
    public function __construct(protected PaymentService $payments) {}

    public function index(Request $request)
    {
        $member = $request->user()->member;

        return view('member.fines', [
            'fines' => $member->fines()->with('loan.copy.book')->latest()->get(),
            'payments' => $member->payments()->with('fine')->latest()->get(),
            'live' => $this->payments->isLive(),
        ]);
    }

    public function pay(Request $request, Fine $fine)
    {
        abort_unless($fine->member_id === $request->user()->member->id, 403);

        if ($fine->status !== 'unpaid') {
            return back()->withErrors(['fine' => 'This fine is already settled.']);
        }
        if ($fine->loan && $fine->loan->returned_at === null) {
            return back()->withErrors(['fine' => 'Please return the book first — the fine keeps growing until it is returned.']);
        }

        $data = $request->validate([
            'method' => ['required', Rule::in(['mtn', 'airtel', 'card'])],
            'phone' => ['required_unless:method,card', 'nullable', 'regex:/^\+?[0-9 ]{9,15}$/'],
        ]);

        $payment = $this->payments->start($fine, $data['method'], $data['phone'] ?? null);

        try {
            return redirect()->away($this->payments->checkoutUrl($payment));
        } catch (Throwable $e) {
            report($e);
            $this->payments->markFailed($payment);

            return back()->withErrors(['fine' => 'Could not reach the payment provider. Please try again later.']);
        }
    }

    /** Flutterwave redirects here with status, tx_ref and transaction_id. */
    public function callback(Request $request)
    {
        $payment = Payment::where('reference', $request->query('tx_ref'))->firstOrFail();

        if ($payment->status === 'pending') {
            $ok = $request->query('status') === 'successful'
                && $request->query('transaction_id')
                && $this->payments->verify($request->query('transaction_id'), $payment);

            $ok ? $this->payments->markSuccessful($payment, $request->query('transaction_id'))
                : $this->payments->markFailed($payment);
        }

        return $this->finish($payment);
    }

    /** Simulated checkout used when no Flutterwave keys are configured. */
    public function sandbox(Request $request, Payment $payment)
    {
        abort_unless($payment->member->user_id === $request->user()->id, 403);
        abort_if($this->payments->isLive(), 404);

        return view('member.sandbox-payment', ['payment' => $payment->load('fine')]);
    }

    public function sandboxComplete(Request $request, Payment $payment)
    {
        abort_unless($payment->member->user_id === $request->user()->id, 403);
        abort_if($this->payments->isLive(), 404);

        if ($payment->status === 'pending') {
            $request->input('outcome') === 'approve'
                ? $this->payments->markSuccessful($payment, 'SANDBOX-'.now()->timestamp)
                : $this->payments->markFailed($payment);
        }

        return $this->finish($payment);
    }

    public function receipt(Request $request, Payment $payment)
    {
        abort_unless($payment->member_id === $request->user()->member->id && $payment->status === 'successful', 404);

        return view('member.receipt', ['payment' => $payment->load('fine.loan.copy.book', 'member.user')]);
    }

    protected function finish(Payment $payment)
    {
        return $payment->status === 'successful'
            ? redirect()->route('my.payments.receipt', $payment)->with('success', 'Payment received. Thank you!')
            : redirect()->route('my.fines')->withErrors(['fine' => 'The payment was not completed.']);
    }
}
