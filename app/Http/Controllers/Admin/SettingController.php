<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public const KEYS = [
        'site_tagline', 'admin_email', 'admin_phone', 'support_whatsapp', 'usd_rate', 'auto_approve_reviews',
        'hero_image', 'hero_video',
    ];

    public function edit()
    {
        return view('admin.settings', [
            'settings' => collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)]),
            'payment_driver' => config('pearl360.payments.driver'),
            'sms_driver' => config('pearl360.sms.driver'),
            'mail_driver' => config('mail.default'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_tagline' => 'nullable|string|max:200',
            'admin_email' => 'required|email',
            'admin_phone' => ['required', 'regex:/^\+?[0-9 ]{9,15}$/'],
            'support_whatsapp' => 'nullable|string|max:20',
            'usd_rate' => 'required|numeric|min:1',
            'auto_approve_reviews' => 'nullable|boolean',
            'hero_image' => 'nullable|image|max:8192',
            'hero_video' => 'nullable|url|max:255',
        ]);

        if ($request->hasFile('hero_image')) {
            $data['hero_image'] = $this->storeUpload($request->file('hero_image'), 'site', Setting::get('hero_image'));
        } else {
            unset($data['hero_image']);
        }
        $data['auto_approve_reviews'] = $request->boolean('auto_approve_reviews') ? '1' : '0';

        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }

        return back()->with('success', 'Settings saved.');
    }

    public function messages()
    {
        $messages = ContactMessage::latest()->paginate(20);
        ContactMessage::where('is_read', false)->update(['is_read' => true]);

        return view('admin.messages', compact('messages'));
    }
}
