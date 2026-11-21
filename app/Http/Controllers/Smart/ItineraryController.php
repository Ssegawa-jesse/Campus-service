<?php

namespace App\Http\Controllers\Smart;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Itinerary;
use App\Models\Operator;
use App\Models\Park;
use App\Services\Murchison\ItineraryEngine;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** MODULE 1 – Smart Itinerary (primary target of the system). */
class ItineraryController extends Controller
{
    public function __construct(private ItineraryEngine $engine)
    {
    }

    public function index(Request $request)
    {
        $defaults = ItineraryEngine::defaults();
        $defaults['must_do'] = session('itinerary_must_do', []);

        return view('smart.itinerary.index', [
            'defaults' => array_merge($defaults, session('itinerary_input', [])),
            'activities' => $this->engine->catalogue(),
            'mustDo' => session('itinerary_must_do', []),
        ]);
    }

    public function generate(Request $request)
    {
        $input = $this->validated($request);
        $plan = $this->engine->build($input);
        session(['itinerary_input' => $plan['input'], 'itinerary_plan' => $plan]);

        return view('smart.itinerary.result', ['plan' => $plan, 'saved' => null, 'operators' => $this->operators()]);
    }

    public function save(Request $request)
    {
        $plan = session('itinerary_plan');
        if (! $plan) {
            return redirect()->route('itinerary.index')->with('error', 'Generate an itinerary first.');
        }
        $itinerary = Itinerary::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title') ?: $plan['input']['days'].'-day Murchison Falls trip',
            'start_date' => $plan['input']['start_date'], 'days' => $plan['input']['days'],
            'visitor_category' => $plan['input']['category'], 'adults' => $plan['input']['adults'], 'children' => $plan['input']['children'],
            'input' => $plan['input'], 'plan' => $plan, 'total_ugx' => $plan['budget']['total'],
        ]);
        session()->forget('itinerary_must_do');

        return redirect()->route('itinerary.show', $itinerary)->with('success', "Itinerary {$itinerary->reference} saved.");
    }

    public function show(Request $request, Itinerary $itinerary)
    {
        $this->authorizeView($request, $itinerary);

        return view('smart.itinerary.result', ['plan' => $itinerary->plan, 'saved' => $itinerary->load('bookings'), 'operators' => $this->operators()]);
    }

    public function destroy(Request $request, Itinerary $itinerary)
    {
        abort_unless($itinerary->user_id === $request->user()->id, 403);
        abort_if($itinerary->bookings()->exists(), 422, 'A booked itinerary cannot be deleted.');
        $itinerary->delete();

        return redirect()->route('tourist.dashboard')->with('success', 'Itinerary deleted.');
    }

    /** "Add to Smart Itinerary" from a 360° hotspot, the map or an activity card. */
    public function add(Request $request, Activity $activity)
    {
        $list = collect(session('itinerary_must_do', []))->push($activity->slug)->unique()->values()->all();
        session(['itinerary_must_do' => $list]);
        $input = session('itinerary_input', []);
        $input['must_do'] = $list;
        session(['itinerary_input' => $input]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'count' => count($list), 'message' => "{$activity->name} added to your Smart Itinerary."]);
        }

        return redirect()->route('itinerary.index')->with('success', "{$activity->name} added to your Smart Itinerary.");
    }

    public function remove(Activity $activity)
    {
        $list = collect(session('itinerary_must_do', []))->reject(fn ($s) => $s === $activity->slug)->values()->all();
        session(['itinerary_must_do' => $list]);
        $input = session('itinerary_input', []);
        $input['must_do'] = $list;
        session(['itinerary_input' => $input]);

        return back()->with('success', "{$activity->name} removed.");
    }

    /**
     * Book a saved itinerary with an approved tour operator. The price is the system-calculated UWA
     * budget, so the booking goes straight to "quoted" and the tourist can pay online at once.
     */
    public function book(Request $request, Itinerary $itinerary, NotificationService $notifier)
    {
        abort_unless($itinerary->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'operator_id' => ['required', Rule::exists('operators', 'id')->where('is_verified', true)],
            'contact_phone' => 'required|string|max:20',
        ]);
        $operator = Operator::findOrFail($data['operator_id']);
        $booking = Booking::create([
            'tourist_id' => $request->user()->id, 'operator_id' => $operator->id, 'park_id' => Park::murchison()->id,
            'itinerary_id' => $itinerary->id, 'travel_date' => $itinerary->start_date, 'group_size' => $itinerary->adults + $itinerary->children,
            'contact_phone' => $data['contact_phone'], 'message' => "Smart Itinerary {$itinerary->reference}: {$itinerary->title}",
            'status' => 'quoted', 'quoted_amount' => $itinerary->total_ugx,
            'operator_response' => 'Price calculated automatically by the Smart Itinerary using UWA tiered rates. Pay online to confirm.',
            'responded_at' => now(),
        ]);
        $itinerary->update(['status' => 'booked']);
        $notifier->bookingCreated($booking);

        return redirect()->route('payments.checkout', $booking)->with('success', 'Booking created. Complete payment with Mobile Money or card.');
    }

    private function validated(Request $request): array
    {
        $cfg = config('murchison');

        return $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'days' => 'required|integer|min:1|max:7',
            'adults' => 'required|integer|min:1|max:30',
            'children' => 'nullable|integer|min:0|max:30',
            'category' => ['required', Rule::in(array_keys($cfg['categories']))],
            'interests' => 'nullable|array',
            'interests.*' => [Rule::in(array_keys($cfg['interests']))],
            'fitness' => 'required|integer|between:1,3',
            'tier' => ['required', Rule::in(array_keys($cfg['tiers']))],
            'transport' => ['required', Rule::in(array_keys($cfg['transport']))],
            'origin' => ['required', Rule::in(array_keys($cfg['origins']))],
            'pace' => ['required', Rule::in(['relaxed', 'full'])],
            'must_do' => 'nullable|array',
            'must_do.*' => 'string|exists:activities,slug',
            'budget_limit' => 'nullable|integer|min:0',
        ]) + ['interests' => [], 'must_do' => [], 'children' => 0];
    }

    private function authorizeView(Request $request, Itinerary $itinerary): void
    {
        $u = $request->user();
        abort_unless($u && ($u->isAdmin() || $itinerary->user_id === $u->id), 403);
    }

    private function operators()
    {
        return Operator::where('is_verified', true)->orderBy('company_name')->get(['id', 'company_name']);
    }
}
