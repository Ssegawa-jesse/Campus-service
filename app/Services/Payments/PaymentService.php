<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    public function gateway(?string $driver = null): PaymentGateway
    {
        $driver ??= config('pearl360.payments.driver');

        return match ($driver) {
            'flutterwave' => new FlutterwaveGateway(config('pearl360.payments.flutterwave')),
            default => new SimulatedGateway(),
        };
    }

    public function start(Booking $booking, string $method, ?string $phone): array
    {
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->tourist_id,
            'tx_ref' => 'P360-TX-'.strtoupper(Str::random(10)),
            'gateway' => config('pearl360.payments.driver'),
            'method' => $method,
            'phone' => $phone,
            'amount' => $booking->quoted_amount,
            'currency' => config('pearl360.payments.currency'),
            'status' => 'pending',
        ]);

        return [$payment, $this->gateway($payment->gateway)->initiate($payment)];
    }

    /** Verify with the gateway and, if successful, mark the booking as paid. Safe to call twice. */
    public function complete(Payment $payment, array $callbackData = []): Payment
    {
        if ($payment->status === 'successful') {
            return $payment;
        }

        $result = $this->gateway($payment->gateway)->verify($payment, $callbackData);

        DB::transaction(function () use ($payment, $result) {
            $payment->update([
                'status' => $result['status'],
                'gateway_reference' => $result['reference'],
                'meta' => $result['raw'],
                'paid_at' => $result['status'] === 'successful' ? now() : null,
            ]);

            if ($result['status'] === 'successful') {
                $payment->booking->update(['status' => 'paid']);
            }
        });

        if ($payment->status === 'successful') {
            $this->notifier->paymentReceived($payment->fresh(['booking.tourist', 'booking.operator', 'booking.park']));
        }

        return $payment;
    }
}
