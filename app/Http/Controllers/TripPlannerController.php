<?php

namespace App\Http\Controllers;

use App\Models\Park;
use App\Models\TripPlan;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TripPlannerController extends Controller
{
    public function index()
    {
        return view('planner.index');
    }

    public function results(Request $request, RecommendationService $engine)
    {
        if ($request->isMethod('get') && ! $request->has('fitness')) {
            return redirect()->route('planner.index');
        }

        $prefs = $request->validate([
            'interests' => 'required|array|min:1',
            'interests.*' => [Rule::in(array_keys(Park::INTERESTS))],
            'fitness' => 'required|integer|between:1,3',
            'budget' => 'nullable|numeric|min:0|max:100000',
            'days' => 'nullable|integer|min:1|max:60',
            'month' => 'nullable|integer|between:1,12',
            'group_size' => 'nullable|integer|min:1|max:50',
        ]);

        $results = $engine->recommend($prefs);

        // Anonymous plans are still stored (without user) so admins can see popular interests.
        $plan = TripPlan::create([
            'user_id' => auth()->id(),
            'title' => 'Trip plan – '.now()->format('d M Y'),
            'preferences' => $prefs,
            'recommended_parks' => $results->map(fn ($r) => ['park_id' => $r['park']->id, 'name' => $r['park']->name, 'score' => $r['score']])->all(),
        ]);

        return view('planner.results', compact('prefs', 'results', 'plan'));
    }
}
