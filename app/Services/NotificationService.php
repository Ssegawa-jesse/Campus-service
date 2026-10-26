<?php

namespace App\Services;

use App\Models\LibraryNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends SMS (Africa's Talking) and email messages and keeps a log of every message.
 * Without Africa's Talking credentials, SMS messages are only written to the log.
 */
class NotificationService
{
    public function notify(User $user, string $subject, string $message): void
    {
        if ($user->phone) {
            $this->sms($user, $message);
        }
        if ($user->email) {
            $this->email($user, $subject, $message);
        }
    }

    public function sms(User $user, string $message): LibraryNotification
    {
        $config = config('services.africastalking');
        $status = 'logged';

        if (! empty($config['username']) && ! empty($config['api_key'])) {
            try {
                $host = $config['username'] === 'sandbox' ? 'api.sandbox.africastalking.com' : 'api.africastalking.com';
                $response = Http::asForm()
                    ->withHeaders(['apiKey' => $config['api_key'], 'Accept' => 'application/json'])
                    ->post("https://{$host}/version1/messaging", array_filter([
                        'username' => $config['username'],
                        'to' => $this->formatPhone($user->phone),
                        'message' => $message,
                        'from' => $config['from'] ?? null,
                    ]));
                $status = $response->successful() ? 'sent' : 'failed';
            } catch (Throwable $e) {
                Log::warning('SMS sending failed: '.$e->getMessage());
                $status = 'failed';
            }
        } else {
            Log::info("SMS to {$user->phone}: {$message}");
        }

        return LibraryNotification::create([
            'user_id' => $user->id,
            'channel' => 'sms',
            'message' => $message,
            'status' => $status,
            'sent_at' => now(),
        ]);
    }

    public function email(User $user, string $subject, string $message): LibraryNotification
    {
        $status = 'sent';

        try {
            Mail::raw($message, fn ($mail) => $mail->to($user->email, $user->name)->subject($subject));
        } catch (Throwable $e) {
            Log::warning('Email sending failed: '.$e->getMessage());
            $status = 'failed';
        }

        return LibraryNotification::create([
            'user_id' => $user->id,
            'channel' => 'email',
            'message' => $message,
            'status' => $status,
            'sent_at' => now(),
        ]);
    }

    /** Convert local Ugandan numbers (07XXXXXXXX) to international format. */
    public function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '256'.substr($digits, 1);
        }

        return '+'.$digits;
    }
}
