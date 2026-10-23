<?php

namespace App\Services;

use App\Mail\SystemNotification;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * One place for every email + SMS the system sends.
 * Admin contact details come from Settings (default 2400725284@mubs.ac.ug / 256741141433).
 */
class NotificationService
{
    public function __construct(private SmsService $sms)
    {
    }

    public function adminEmail(): string
    {
        return setting('admin_email', config('pearl360.admin_email'));
    }

    public function adminPhone(): string
    {
        return setting('admin_phone', config('pearl360.admin_phone'));
    }

    public function welcome(User $user): void
    {
        $this->email($user->email, 'Welcome to '.config('pearl360.name'), [
            "Hello {$user->name},",
            'Your account has been created. You can now explore 360° tours of Uganda\'s national parks, plan your trip and book with trusted tour operators.',
        ], route('login'), 'Log in');

        $this->smsUser($user, 'Welcome to '.config('pearl360.name').'! Explore Uganda\'s parks in 360° and plan your safari.');

        if ($user->isOperator()) {
            $company = $user->operator?->company_name ?? $user->name;
            $this->notifyAdmins('New tour operator awaiting approval', [
                "{$company} ({$user->name}, {$user->email}, {$user->phone}) registered as a tour operator.",
                'Open the admin dashboard to approve or reject them.',
            ], route('admin.operators.index', ['status' => 'pending']), 'Review now',
                "Pearl360: new operator {$company} is awaiting your approval.");
        } else {
            $this->notifyAdmins('New tourist registered', [
                "{$user->name} ({$user->email}) created a tourist account.",
            ], route('admin.users.index'), 'View users');
        }
    }

    public function bookingCreated(Booking $booking): void
    {
        $booking->loadMissing('tourist', 'operator.user', 'park');
        $b = $booking;

        $this->email($b->operator->contact_email, "New booking enquiry {$b->reference}", [
            "{$b->tourist->name} wants to visit {$b->park->name} on {$b->travel_date->format('d M Y')} ({$b->group_size} people).",
            $b->message ? "Message: {$b->message}" : null,
            'Please respond with a quote from your operator dashboard.',
        ], route('operator.bookings.show', $b), 'Respond now');
        $this->sms->send($b->operator->contact_phone, "Pearl360: new enquiry {$b->reference} for {$b->park->name}, {$b->travel_date->format('d M')}, {$b->group_size} pax. Log in to send a quote.");

        $this->email($b->tourist->email, "Enquiry {$b->reference} sent", [
            "Your enquiry to {$b->operator->company_name} for {$b->park->name} has been sent. You will get an SMS and email when they reply with a quote.",
        ], route('tourist.bookings.show', $b), 'View booking');
        $this->smsUser($b->tourist, "Pearl360: enquiry {$b->reference} sent to {$b->operator->company_name}. We'll notify you when they reply.");

        $this->notifyAdmins(null, [], null, null, "Pearl360 admin: new enquiry {$b->reference} ({$b->park->name}) by {$b->tourist->name}.");
    }

    public function bookingUpdated(Booking $booking): void
    {
        $booking->loadMissing('tourist', 'operator', 'park');
        $b = $booking;

        $text = match ($b->status) {
            'quoted' => "{$b->operator->company_name} quoted ".ugx($b->quoted_amount)." for {$b->park->name}. Pay online to secure your trip.",
            'confirmed' => "Your trip to {$b->park->name} on {$b->travel_date->format('d M Y')} is CONFIRMED by {$b->operator->company_name}.",
            'declined' => "{$b->operator->company_name} could not take booking {$b->reference}. Try another operator.",
            'completed' => "Thanks for visiting {$b->park->name}! Please leave a review.",
            default => "Booking {$b->reference} is now: {$b->statusLabel()}.",
        };

        $this->email($b->tourist->email, "Booking {$b->reference}: {$b->statusLabel()}", [
            $text,
            $b->operator_response ? "Operator note: {$b->operator_response}" : null,
        ], route('tourist.bookings.show', $b), $b->status === 'quoted' ? 'Pay now' : 'View booking');
        $this->smsUser($b->tourist, "Pearl360 {$b->reference}: {$text}");
    }

    public function bookingCancelled(Booking $booking): void
    {
        $booking->loadMissing('tourist', 'operator', 'park');
        $this->email($booking->operator->contact_email, "Booking {$booking->reference} cancelled", [
            "{$booking->tourist->name} cancelled the booking for {$booking->park->name}.",
        ], route('operator.bookings.show', $booking), 'View booking');
        $this->sms->send($booking->operator->contact_phone, "Pearl360: booking {$booking->reference} was cancelled by the tourist.");
    }

    public function paymentReceived(Payment $payment): void
    {
        $b = $payment->booking;
        $amount = ugx($payment->amount);

        $this->email($b->tourist->email, "Payment received – {$b->reference}", [
            "We received your payment of {$amount} ({$payment->methodLabel()}) for {$b->park->name}.",
            "Transaction reference: {$payment->tx_ref}",
            "{$b->operator->company_name} will now confirm your trip.",
        ], route('tourist.bookings.show', $b), 'View receipt');
        $this->smsUser($b->tourist, "Pearl360: payment of {$amount} received for {$b->reference}. Ref {$payment->tx_ref}. Thank you!");

        $this->email($b->operator->contact_email, "Booking {$b->reference} has been paid", [
            "{$b->tourist->name} paid {$amount}. Please confirm the trip.",
        ], route('operator.bookings.show', $b), 'Confirm trip');
        $this->sms->send($b->operator->contact_phone, "Pearl360: {$b->reference} PAID {$amount}. Please confirm the trip.");

        $this->notifyAdmins("Payment received {$amount}", [
            "Booking {$b->reference} ({$b->park->name}) paid via {$payment->methodLabel()}.",
        ], route('admin.payments.index'), 'View payments', "Pearl360 admin: {$amount} received for {$b->reference}.");
    }

    public function operatorVerified(\App\Models\Operator $operator): void
    {
        $this->email($operator->contact_email, 'Your operator account is approved', [
            "Congratulations! {$operator->company_name} has been approved on ".config('pearl360.name').'. Your packages are now visible and tourists can send you booking enquiries.',
        ], route('operator.dashboard'), 'Open dashboard');
        $this->sms->send($operator->contact_phone, "Pearl360: {$operator->company_name} is now APPROVED. You can receive bookings.");
    }

    public function operatorRejected(\App\Models\Operator $operator): void
    {
        $this->email($operator->contact_email, 'Your operator application was not approved', [
            "Unfortunately {$operator->company_name} was not approved on ".config('pearl360.name').'.',
            $operator->rejection_reason ? "Reason: {$operator->rejection_reason}" : null,
            'Update your company profile (licence number, contacts, parks served) and contact us to be reviewed again.',
        ], route('operator.profile.edit'), 'Update profile');
        $this->sms->send($operator->contact_phone, "Pearl360: {$operator->company_name} was not approved. Check your email for the reason.");
    }

    public function contactReceived(array $data): void
    {
        $this->notifyAdmins('Contact form: '.$data['subject'], [
            "From: {$data['name']} <{$data['email']}> ".($data['phone'] ?? ''),
            $data['message'],
        ], route('admin.messages.index'), 'Open inbox');
    }

    /**
     * Alert EVERY active administrator (there can be several) plus the main contact in Settings.
     * Pass a null subject to send SMS only, or a null SMS to send email only.
     */
    public function notifyAdmins(?string $subject, array $lines, ?string $url, ?string $action, ?string $sms = null): void
    {
        $admins = User::admins()->get(['id', 'email', 'phone', 'sms_opt_in']);
        $emails = $admins->pluck('email')->push($this->adminEmail())->filter()->map(fn ($e) => strtolower($e))->unique();
        $phones = $admins->filter(fn ($a) => $a->sms_opt_in)->pluck('phone')->push($this->adminPhone())
            ->filter()->map(fn ($p) => $this->sms->normalise($p))->filter()->unique();

        if ($subject) {
            foreach ($emails as $email) {
                $this->email($email, $subject, $lines, $url, $action);
            }
        }
        if ($sms) {
            foreach ($phones as $phone) {
                $this->sms->send($phone, $sms);
            }
        }
    }

    private function smsUser(User $user, string $message): void
    {
        if ($user->sms_opt_in && $user->phone) {
            $this->sms->send($user->phone, $message);
        }
    }

    private function email(?string $to, string $subject, array $lines, ?string $url = null, ?string $action = null): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new SystemNotification($subject, array_values(array_filter($lines)), $url, $action));
        } catch (\Throwable $e) {
            Log::warning('Email failed: '.$e->getMessage());
        }
    }
}
