<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Park;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Activities, UWA tiered prices and the scheduling rules used by the Smart Itinerary engine. */
class UwaActivityController extends Controller
{
    public function index()
    {
        return view('admin.uwa.index', ['activities' => Activity::with('place')->withCount('scenes')->orderByDesc('priority')->get(), 'places' => Place::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $data['slug'] = Str::slug($data['name']);
        $data['park_id'] = Park::murchison()->id;
        Activity::create($data);

        return back()->with('success', 'Activity added.');
    }

    public function update(Request $request, Activity $activity)
    {
        $activity->update($this->data($request));

        return back()->with('success', "{$activity->name} updated.");
    }

    private function data(Request $request): array
    {
        $slots = array_keys(config('murchison.slots'));
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'category' => ['required', Rule::in(array_keys(Activity::CATEGORIES))],
            'place_id' => 'required|exists:places,id',
            'description' => 'required|string|max:2000',
            'price_fnr_usd' => 'required|numeric|min:0', 'price_fr_usd' => 'required|numeric|min:0', 'price_eac_ugx' => 'required|integer|min:0',
            'group_fee_usd' => 'nullable|numeric|min:0', 'group_fee_ugx' => 'nullable|integer|min:0',
            'hours' => 'required|numeric|min:0.5|max:12',
            'fitness_level' => 'required|integer|between:1,3',
            'priority' => 'required|integer|between:1,10',
            'min_age' => 'nullable|integer|min:0|max:18',
            'slots' => 'required|array|min:1', 'slots.*' => [Rule::in($slots)],
            'interests' => 'nullable|array', 'interests.*' => [Rule::in(array_keys(config('murchison.interests')))],
            'wet_season_note' => 'nullable|string|max:255',
        ]);

        return array_merge(['interests' => [], 'group_fee_usd' => 0, 'group_fee_ugx' => 0, 'min_age' => 0, 'wet_season_note' => null],
            array_filter($data, fn ($v) => $v !== null), [
                'is_active' => $request->boolean('is_active'), 'indicative_cost' => $data['price_fnr_usd'],
                'duration' => rtrim(rtrim(number_format($data['hours'], 1), '0'), '.').' hours',
            ]);
    }
}
