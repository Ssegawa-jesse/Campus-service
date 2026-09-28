<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Park;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function store(Request $request, Park $park)
    {
        $park->activities()->create($this->validated($request));

        return back()->with('success', 'Activity added.');
    }

    public function update(Request $request, Activity $activity)
    {
        $activity->update($this->validated($request));

        return back()->with('success', 'Activity updated.');
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();

        return back()->with('success', 'Activity removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'category' => ['required', Rule::in(array_keys(Activity::CATEGORIES))],
            'fitness_level' => 'required|integer|between:1,3',
            'duration' => 'nullable|string|max:60',
            'indicative_cost' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:2000',
        ]);
    }
}
