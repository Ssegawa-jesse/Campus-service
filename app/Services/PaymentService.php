<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Fine;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fine payments through Flutterwave (MTN Mobile Money, Airtel Money, card).
 * Without Flutterwave keys the system runs in sandbox mode, where a simulated
 * checkout page lets you approve or decline the payment for demonstration.
 */
class PaymentService
{
    public function isLive(): bool
    {
        return filled(config('services.flutterwave.secret_key'));
    }

    public function start(Fine $fine, string $method, ?string $phone): Payment
    {
        return Payment::create([
            'fine_id' => $fine->id,
            'member_id' => $fine->member_id,
            'amount' => $fine->amount,
            'method' => $method,
            'phone' => $phone,
            'reference' => 'LIB-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
            'status' => 'pending',
        ]);
    }

    /** URL the member is redirected to in order to complete the payment. */
    public function checkoutUrl(Payment $payment): string
    {
        if (! $this->isLive()) {
            return route('payments.sandbox', $payment);
        }

        $user = $payment->member->user;
        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $payment->reference,
                'amount' => $payment->amount,
                'currency' => 'UGX',
                'redirect_url' => route('payments.callback'),
                'payment_options' => $payment->method === 'card' ? 'card' : 'mobilemoneyuganda',
                'customer' => [
                    'email' => $user->email,
                    'phonenumber' => $payment->phone ?: $user->phone,
                    'name' => $user->name,
                ],
                'customizations' => ['title' => config('app.name').' fine payment'],
            ])->throw();

        return $response->json('data.link');
    }

    /** Ask Flutterwave whether a transaction succeeded. */
    public function verify(string $transactionId, Payment $payment): bool
    {
        $data = Http::withToken(config('services.flutterwave.secret_key'))
            ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify")
            ->json('data');

        return ($data['status'] ?? null) === 'successful'
            && ($data['tx_ref'] ?? null) === $payment->reference
            && (int) ($data['amount'] ?? 0) >= $payment->amount
            && ($data['currency'] ?? null) === 'UGX';
    }

    public function markSuccessful(Payment $payment, ?string $gatewayRef = null, ?int $staffId = null): Payment
    {
        DB::transaction(function () use ($payment, $gatewayRef, $staffId) {
            $payment->update([
                'status' => 'successful',
                'gateway_ref' => $gatewayRef,
                'paid_at' => now(),
                'recorded_by' => $staffId,
            ]);
            $payment->fine->update(['status' => 'paid']);
        });

        AuditLog::record('fine.paid', "Fine #{$payment->fine_id} paid UGX ".number_format($payment->amount)." via {$payment->methodLabel()} ({$payment->reference})");

        return $payment;
    }

    public function markFailed(Payment $payment): Payment
    {
        $payment->update(['status' => 'failed']);

        return $payment;
    }
}
