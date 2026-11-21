<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantLog;
use App\Models\EmailLog;
use App\Models\Itinerary;
use App\Models\SmsLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;

/** SMS & email notification logs with a test sender; AI assistant logs; all saved itineraries. */
class NotificationLogController extends Controller
{
    public function index()
    {
        return view('admin.logs.notifications', [
            'sms' => SmsLog::latest()->paginate(15, ['*'], 'sms'),
            'emails' => EmailLog::latest()->paginate(15, ['*'], 'emails'),
            'adminEmail' => setting('admin_email', config('pearl360.admin_email')),
            'adminPhone' => setting('admin_phone', config('pearl360.admin_phone')),
            'smsDriver' => config('pearl360.sms.driver'),
            'mailDriver' => config('mail.default'),
        ]);
    }

    public function test(Request $request, NotificationService $notifier)
    {
        $data = $request->validate([
            'channel' => 'required|in:sms,email,both',
            'email' => 'required_unless:channel,sms|nullable|email',
            'phone' => 'required_unless:channel,email|nullable|string|max:20',
            'message' => 'nullable|string|max:300',
        ]);
        if ($data['channel'] !== 'email') {
            $notifier->sms()->send($data['phone'], ($data['message'] ?? null) ?: 'MFNP Smart Trip: test SMS from the notification simulator.');
        }
        if ($data['channel'] !== 'sms') {
            $notifier->sendTestEmail($data['email']);
        }

        return back()->with('success', 'Test notification sent – see the logs below.');
    }

    public function assistant()
    {
        return view('admin.logs.assistant', [
            'logs' => AssistantLog::with('user')->latest()->paginate(25),
            'intents' => AssistantLog::selectRaw('intent, count(*) as total')->groupBy('intent')->orderByDesc('total')->pluck('total', 'intent'),
        ]);
    }

    public function itineraries()
    {
        return view('admin.logs.itineraries', ['itineraries' => Itinerary::with('user')->withCount('bookings')->latest()->paginate(25)]);
    }
}
