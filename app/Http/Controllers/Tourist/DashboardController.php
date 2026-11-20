<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Models\Park;
use App\Models\TripPlan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('tourist.dashboard', [
            'user' => $user,
            'favourites' => $user->favourites()->withCount('scenes')->get(),
            'plans' => $user->tripPlans()->latest()->take(6)->get(),
            'bookings' => $user->bookings()->with('park', 'operator')->latest()->take(5)->get(),
            'stats' => [
                'bookings' => $user->bookings()->count(),
                'paid' => $user->bookings()->whereIn('status', ['paid', 'confirmed', 'completed'])->count(),
                'favourites' => $user->favourites()->count(),
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

    /** Attach an anonymous plan (created on the results page) to the logged-in tourist. */
    public function savePlan(Request $request)
    {
        $data = $request->validate(['plan_id' => 'required|exists:trip_plans,id', 'title' => 'nullable|string|max:120']);
        $plan = TripPlan::findOrFail($data['plan_id']);
        abort_if($plan->user_id && $plan->user_id !== $request->user()->id, 403);

        $plan->update(['user_id' => $request->user()->id, 'title' => $data['title'] ?: $plan->title]);

        return redirect()->route('tourist.dashboard')->with('success', 'Trip plan saved.');
    }

    public function deletePlan(Request $request, TripPlan $plan)
    {
        abort_unless($plan->user_id === $request->user()->id, 403);
        $plan->delete();

        return back()->with('success', 'Trip plan deleted.');
    }
}
