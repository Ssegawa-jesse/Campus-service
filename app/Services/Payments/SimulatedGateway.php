<?php

namespace App\Services\Payments;

use App\Models\Payment;

/**
 * Offline payment simulator for demos and the project defence.
 * Shows a mock mobile-money prompt page where the user approves or declines.
 */
class SimulatedGateway implements PaymentGateway
{
    public function initiate(Payment $payment): string
    {
        return route('payments.simulate', $payment->tx_ref);
    }

    public function verify(Payment $payment, array $callbackData = []): array
    {
        $status = ($callbackData['status'] ?? null) === 'successful' ? 'successful' : 'failed';

        return [
            'status' => $status,
            'reference' => $status === 'successful' ? 'SIM-'.strtoupper(substr(md5($payment->tx_ref), 0, 10)) : null,
            'raw' => $callbackData,
        ];
    }
}
