<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Operator;
use App\Models\Scene;
use App\Models\TourPackage;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $park = SmartTripController::park();

        return view('home', [
            'park' => $park,
            'packages' => TourPackage::with('park', 'operator')->where('is_active', true)
                ->whereHas('operator', fn ($q) => $q->where('is_verified', true))->latest()->take(3)->get(),
            'stats' => [
                'activities' => count(config('murchison.activities')),
                'scenes' => Scene::count(),
                'operators' => Operator::where('is_verified', true)->count(),
                'trips' => Booking::whereIn('status', ['paid', 'confirmed', 'completed'])->count(),
            ],
        ]);
    }

    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }

    public function sendContact(Request $request, NotificationService $notifier)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:150',
            'message' => 'required|string|max:3000',
        ]);

        ContactMessage::create($data);
        $notifier->contactReceived($data);

        return back()->with('success', 'Thank you! Your message has been sent. We will get back to you soon.');
    }
}
