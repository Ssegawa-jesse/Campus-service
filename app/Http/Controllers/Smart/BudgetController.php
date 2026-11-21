<?php

namespace App\Http\Controllers\Smart;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\Murchison\BudgetCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** MODULE 2 – Travel Budgeting (UWA tiered pricing). */
class BudgetController extends Controller
{
    public function index(Request $request, BudgetCalculator $calc)
    {
        $cfg = config('murchison');
        $in = $request->validate([
            'days' => 'nullable|integer|min:1|max:14', 'adults' => 'nullable|integer|min:1|max:30', 'children' => 'nullable|integer|min:0|max:30',
            'category' => ['nullable', Rule::in(array_keys($cfg['categories']))], 'tier' => ['nullable', Rule::in(array_keys($cfg['tiers']))],
            'transport' => ['nullable', Rule::in(array_keys($cfg['transport']))], 'origin' => ['nullable', Rule::in(array_keys($cfg['origins']))],
            'activities' => 'nullable|array', 'activities.*' => 'string',
        ]);
        $in = array_merge(['days' => 3, 'adults' => 2, 'children' => 0, 'category' => 'fnr', 'tier' => 'mid', 'transport' => 'tour', 'origin' => 'kampala',
            'activities' => $request->has('days') ? [] : ['victoria-nile-boat-cruise', 'north-bank-game-drive', 'hike-to-the-top-of-the-falls']], $in);

        $activities = Activity::with('place')->active()->orderByDesc('priority')->get();

        return view('smart.budget', ['in' => $in, 'result' => $calc->calculate($in), 'activities' => $activities, 'calc' => $calc]);
    }
}
