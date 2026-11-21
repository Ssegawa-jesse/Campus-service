<?php

namespace App\Http\Controllers;

use App\Models\Park;
use App\Models\Scene;
use App\Models\TripPlan;
use App\Services\Murchison\SmartItinerary;
use App\Services\Murchison\TravelAssistant;
use App\Services\Murchison\TripCost;
use Illuminate\Http\Request;

/** Public pages of the Murchison Falls smart tourism platform. */
class SmartTripController extends Controller
{
    public static function park(): ?Park
    {
        return Park::where('slug', config('murchison.park_slug'))->first();
    }

    /** activity key => id of its first 360° scene (for the "View in 360°" links). */
    public static function scenesByActivity(): array
    {
        $park = self::park();

        return $park ? $park->scenes()->whereNotNull('activity_key')->orderBy('sort_order')->get(['id', 'activity_key'])
            ->unique('activity_key')->pluck('id', 'activity_key')->all() : [];
    }

    /** Smart itinerary: the form, and the plan when the form has been submitted (or a shared link is opened). */
    public function plan(Request $request, SmartItinerary $engine)
    {
        $input = SmartItinerary::decode($request->query('p'));
        $error = null;
        if (! $input && $request->has('build')) {
            $request->validate([
                'start_date' => 'required|date|after_or_equal:today',
                'days' => 'required|integer|min:1|max:6',
                'adults' => 'required|integer|min:1|max:30',
                'children' => 'nullable|integer|min:0|max:30',
            ]);
            $input = SmartItinerary::normalize($request->only(['start_date', 'days', 'adults', 'children', 'residency', 'interests', 'fitness', 'tier', 'transport', 'origin', 'pace', 'must_do']));
            if (! $input['interests'] && ! $input['must_do']) {
                $error = 'Choose at least one interest.';
                $input = null;
            }
        }
        $plan = $input ? $engine->build($input) : null;

        return view('smart.plan', [
            'form' => $input ?? SmartItinerary::normalize($request->old() ?: SmartItinerary::defaults()),
            'plan' => $plan,
            'error' => $error,
            'scenes' => $plan ? self::scenesByActivity() : [],
            'saved' => $request->user()?->isTourist() ? TripPlan::where('user_id', $request->user()->id)->latest()->take(10)->get() : collect(),
        ]);
    }

    /** Tourists can save itineraries to their account. */
    public function savePlan(Request $request, SmartItinerary $engine)
    {
        $input = SmartItinerary::decode($request->input('p'));
        abort_unless($input, 422);
        $plan = $engine->build($input);
        TripPlan::create([
            'user_id' => $request->user()->id,
            'title' => "{$input['days']}-day trip from ".\Carbon\Carbon::parse($input['start_date'])->format('j M Y'),
            'preferences' => $input,
            'recommended_parks' => ['code' => $plan['code'], 'summary' => $engine->summary($plan), 'total_ugx' => round($plan['total'])],
        ]);

        return back()->with('success', 'Itinerary saved to your account.');
    }

    public function deletePlan(Request $request, TripPlan $plan)
    {
        abort_unless($plan->user_id === $request->user()->id, 403);
        $plan->delete();

        return back()->with('success', 'Itinerary deleted.');
    }

    public function budget(Request $request)
    {
        $acts = array_keys(config('murchison.activities'));
        $in = [
            'days' => min(14, max(1, $request->integer('days', 3))),
            'adults' => min(30, max(1, $request->integer('adults', 2))),
            'children' => min(30, max(0, $request->integer('children', 0))),
            'residency' => in_array($request->query('residency'), array_keys(config('murchison.residency')), true) ? $request->query('residency') : 'fnr',
            'tier' => in_array($request->query('tier'), array_keys(config('murchison.tiers')), true) ? $request->query('tier') : 'mid',
            'transport' => in_array($request->query('transport'), array_keys(config('murchison.transport')), true) ? $request->query('transport') : 'tour',
            'origin' => in_array($request->query('origin'), array_keys(config('murchison.origins')), true) ? $request->query('origin') : 'kampala',
            'activity_ids' => $request->has('days') ? array_values(array_intersect((array) $request->query('activities', []), $acts)) : ['launch', 'game-drive', 'top-falls'],
        ];

        return view('smart.budget', ['in' => $in, 'budget' => TripCost::compute($in)]);
    }

    public function map(Request $request)
    {
        $highlight = array_values(array_intersect(explode(',', (string) $request->query('ids')), array_keys(config('murchison.attractions'))));

        return view('smart.map', ['highlight' => $highlight]);
    }

    public function explore(Request $request)
    {
        $park = self::park();
        abort_unless($park, 404);

        return app(TourController::class)->show($park);
    }

    public function assistant()
    {
        return view('smart.assistant', ['aiEnabled' => (bool) config('services.anthropic.key')]);
    }

    public function ask(Request $request, TravelAssistant $assistant)
    {
        $data = $request->validate([
            'messages' => 'required|array|min:1|max:12',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:2000',
        ]);
        $messages = array_values(array_map(fn ($m) => ['role' => $m['role'], 'content' => $m['content']], $data['messages']));
        while ($messages && $messages[0]['role'] !== 'user') {
            array_shift($messages);
        }
        abort_unless($messages && end($messages)['role'] === 'user', 422);

        return response()->json($assistant->answer($messages));
    }

    public function updates()
    {
        return view('smart.updates');
    }

    public function about()
    {
        $park = self::park();
        abort_unless($park, 404);

        return app(ParkController::class)->show($park);
    }
}
