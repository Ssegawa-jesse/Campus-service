<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Models\Park;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('tourist.dashboard', [
            'user' => $user,
            'favourites' => $user->favourites()->withCount('scenes')->get(),
            'itineraries' => $user->itineraries()->withCount('bookings')->latest()->take(6)->get(),
            'bookings' => $user->bookings()->with('park', 'operator')->latest()->take(5)->get(),
            'stats' => [
                'bookings' => $user->bookings()->count(),
                'paid' => $user->bookings()->whereIn('status', ['paid', 'confirmed', 'completed'])->count(),
                'itineraries' => $user->itineraries()->count(),
                'reviews' => $user->reviews()->count(),
            ],
            'actionNeeded' => $user->bookings()->where('status', 'quoted')->with('park')->get(),
        ]);
    }

    public function toggleFavourite(Request $request, Park $park)
    {
        $result = $request->user()->favourites()->toggle($park->id);
        $added = ! empty($result['attached']);

        if ($request->expectsJson()) {
            return response()->json(['favourite' => $added]);
        }

        return back()->with('success', $added ? "{$park->name} saved to your favourites." : "{$park->name} removed from favourites.");
    }
}
