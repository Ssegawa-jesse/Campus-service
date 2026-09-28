<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Flutterwave Standard checkout – supports MTN Mobile Money, Airtel Money and cards in Uganda.
 * Docs: https://developer.flutterwave.com/docs/collecting-payments/standard
 */
class FlutterwaveGateway implements PaymentGateway
{
    public function __construct(private array $config)
    {
    }

    public function initiate(Payment $payment): string
    {
        $payment->loadMissing('booking.park', 'user');

        $options = match ($payment->method) {
            'card' => 'card',
            default => 'mobilemoneyuganda',
        };

        $response = Http::withToken($this->config['secret_key'])
            ->acceptJson()
            ->post($this->config['base_url'].'/payments', [
                'tx_ref' => $payment->tx_ref,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'redirect_url' => route('payments.callback'),
                'payment_options' => $options,
                'customer' => [
                    'email' => $payment->user->email,
                    'phonenumber' => $payment->phone ?: $payment->user->phone,
                    'name' => $payment->user->name,
                ],
                'customizations' => [
                    'title' => config('pearl360.name'),
                    'description' => 'Booking '.$payment->booking->reference.' – '.$payment->booking->park->name,
                    'logo' => asset('images/logo.svg'),
                ],
                'meta' => ['booking' => $payment->booking->reference],
            ]);

        if (! $response->successful() || $response->json('status') !== 'success') {
            throw new RuntimeException('Flutterwave: '.($response->json('message') ?? 'could not start payment'));
        }

        return $response->json('data.link');
    }

    public function verify(Payment $payment, array $callbackData = []): array
    {
        $response = Http::withToken($this->config['secret_key'])
            ->acceptJson()
            ->get($this->config['base_url'].'/transactions/verify_by_reference', ['tx_ref' => $payment->tx_ref]);

        $data = $response->json('data') ?? [];

        $ok = $response->successful()
            && ($data['status'] ?? null) === 'successful'
            && (int) ($data['amount'] ?? 0) >= $payment->amount
            && ($data['currency'] ?? null) === $payment->currency;

        return [
            'status' => $ok ? 'successful' : (($data['status'] ?? null) === 'pending' ? 'pending' : 'failed'),
            'reference' => isset($data['id']) ? (string) $data['id'] : null,
            'raw' => $data,
        ];
    }
}
