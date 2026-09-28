<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\Sms\SmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SmsController extends Controller
{
    public function index()
    {
        return view('admin.sms.index', [
            'logs' => SmsLog::latest()->paginate(25),
            'driver' => config('pearl360.sms.driver'),
        ]);
    }

    /** Send one SMS, or broadcast to all tourists / operators (e.g. promotions, park alerts). */
    public function send(Request $request, SmsService $sms)
    {
        $data = $request->validate([
            'audience' => ['required', Rule::in(['single', 'tourists', 'operators'])],
            'to' => 'required_if:audience,single|nullable|string|max:20',
            'message' => 'required|string|max:459',
        ]);

        $numbers = match ($data['audience']) {
            'single' => [$data['to']],
            'tourists' => User::where('role', 'tourist')->where('sms_opt_in', true)->whereNotNull('phone')->pluck('phone')->all(),
            'operators' => User::where('role', 'operator')->whereNotNull('phone')->pluck('phone')->all(),
        };

        $sent = 0;
        foreach (array_unique($numbers) as $n) {
            if ($sms->send($n, $data['message'])) {
                $sent++;
            }
        }

        return back()->with('success', "{$sent} SMS processed via '".config('pearl360.sms.driver')."'.");
    }
}
