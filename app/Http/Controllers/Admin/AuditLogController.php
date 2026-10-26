<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LibraryNotification;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.audit', [
            'logs' => AuditLog::with('user')
                ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q->where('action', 'like', "%{$term}%")->orWhere('details', 'like', "%{$term}%")))
                ->latest('id')
                ->paginate(40)
                ->withQueryString(),
        ]);
    }

    public function notifications()
    {
        return view('admin.notifications', [
            'notifications' => LibraryNotification::with('user')->latest('id')->paginate(40),
        ]);
    }
}
