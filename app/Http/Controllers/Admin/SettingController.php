<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\LibrarySettings;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', [
            'settings' => LibrarySettings::all(),
            'labels' => LibrarySettings::LABELS,
            'smsLive' => filled(config('services.africastalking.api_key')),
            'paymentsLive' => filled(config('services.flutterwave.secret_key')),
        ]);
    }

    public function update(Request $request)
    {
        $rules = collect(LibrarySettings::DEFAULTS)
            ->map(fn ($default, $key) => $key === 'library_name'
                ? ['required', 'string', 'max:100']
                : ['required', 'integer', 'min:0', 'max:1000000'])
            ->all();

        LibrarySettings::set($request->validate($rules));
        AuditLog::record('settings.updated', 'Library rules updated');

        return back()->with('success', 'Settings saved.');
    }
}
