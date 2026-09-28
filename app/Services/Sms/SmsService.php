<?php

namespace App\Services\Sms;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends SMS through Africa's Talking (works with MTN, Airtel & Lyca in Uganda),
 * or just logs them when SMS_DRIVER=log. Every message is stored in sms_logs.
 */
class SmsService
{
    public function send(?string $to, string $message): ?SmsLog
    {
        $to = $this->normalise($to);
        if (! $to) {
            return null;
        }

        $driver = config('pearl360.sms.driver');
        $message = mb_substr($message, 0, 459); // max 3 SMS parts

        $log = SmsLog::create(['to' => $to, 'message' => $message, 'driver' => $driver, 'status' => 'queued']);

        try {
            if ($driver === 'africastalking') {
                $this->sendViaAfricasTalking($log);
            } else {
                Log::info("[SMS to {$to}] {$message}");
                $log->update(['status' => 'logged']);
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'response' => $e->getMessage()]);
        }

        return $log;
    }

    /** Convert 0741141433 / 256741141433 / +256741141433 into +256741141433. */
    public function normalise(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '256'.substr($digits, 1);
        }
        if (strlen($digits) === 9) {
            $digits = '256'.$digits;
        }

        return '+'.$digits;
    }

    private function sendViaAfricasTalking(SmsLog $log): void
    {
        $cfg = config('pearl360.sms.africastalking');
        $url = $cfg['sandbox']
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';

        $payload = array_filter([
            'username' => $cfg['username'],
            'to' => $log->to,
            'message' => $log->message,
            'from' => $cfg['from'],
        ]);

        $response = Http::asForm()
            ->withHeaders(['apiKey' => $cfg['api_key'], 'Accept' => 'application/json'])
            ->post($url, $payload);

        $status = $response->json('SMSMessageData.Recipients.0.status');

        $log->update([
            'status' => $response->successful() && $status === 'Success' ? 'sent' : 'failed',
            'response' => $response->body(),
        ]);
    }
}
