<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Smart Navigation map points (gates, jetty, trails, airfield, attractions). */
class PlaceController extends Controller
{
    public function index()
    {
        return view('admin.places.index', ['places' => Place::withCount('activities')->orderBy('type')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.places.form', ['place' => new Place(['type' => 'attraction', 'zone' => 'south', 'latitude' => 2.28, 'longitude' => 31.6, 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $data['slug'] = Str::slug($data['name']).(Place::where('slug', Str::slug($data['name']))->exists() ? '-'.Str::lower(Str::random(4)) : '');
        Place::create($data);

        return redirect()->route('admin.places.index')->with('success', 'Place added to the map.');
    }

    public function edit(Place $place)
    {
        return view('admin.places.form', ['place' => $place]);
    }

    public function update(Request $request, Place $place)
    {
        $place->update($this->data($request));

        return redirect()->route('admin.places.index')->with('success', 'Place updated.');
    }

    public function destroy(Place $place)
    {
        $place->delete();

        return back()->with('success', 'Place removed from the map.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(Place::TYPES))],
            'zone' => ['required', Rule::in(array_keys(Place::ZONES))],
            'latitude' => 'required|numeric|between:1.3,2.9',
            'longitude' => 'required|numeric|between:31.0,32.6',
            'description' => 'nullable|string|max:2000',
            'tip' => 'nullable|string|max:255',
            'opening_hours' => 'nullable|string|max:120',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
