<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentGateway
{
    /**
     * Start a payment. Returns a URL the tourist should be redirected to
     * (hosted checkout page or our own simulator page).
     */
    public function initiate(Payment $payment): string;

    /**
     * Confirm with the gateway whether the payment really succeeded.
     * Returns ['status' => 'successful'|'failed'|'pending', 'reference' => ?string, 'raw' => array]
     */
    public function verify(Payment $payment, array $callbackData = []): array;
}
