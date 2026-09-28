<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Operator;
use App\Models\Park;
use App\Models\Review;
use App\Models\Scene;
use App\Models\TourPackage;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $parks = Park::published()->withCount('scenes')->withAvg(['reviews' => fn ($q) => $q->where('is_approved', true)], 'rating')->orderBy('name')->get();

        return view('home', [
            'parks' => $parks,
            'featured' => $parks->sortByDesc('views_count')->take(6),
            'hiddenGems' => $parks->sortBy('views_count')->take(3),
            'packages' => TourPackage::with('park', 'operator')->where('is_active', true)
                ->whereHas('operator', fn ($q) => $q->where('is_verified', true))->latest()->take(6)->get(),
            'reviews' => Review::with('user', 'park')->where('is_approved', true)->whereNotNull('park_id')->latest()->take(3)->get(),
            'stats' => [
                'parks' => $parks->count(),
                'scenes' => Scene::count(),
                'operators' => Operator::where('is_verified', true)->count(),
                'trips' => Booking::whereIn('status', ['paid', 'confirmed', 'completed'])->count(),
            ],
        ]);
    }

    public function map()
    {
        return view('map', ['parks' => Park::published()->withCount('scenes')->orderBy('name')->get()]);
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
