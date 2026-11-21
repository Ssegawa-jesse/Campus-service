<?php

namespace App\Http\Controllers\Smart;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Place;
use App\Services\Murchison\RouteService;
use Illuminate\Http\Request;

/** MODULE 3 – Smart Navigation (interactive park map with distances & travel times). */
class NavigationController extends Controller
{
    public function index()
    {
        $places = Place::where('is_active', true)->with('activities:id,place_id,name,slug')->orderBy('type')->orderBy('name')->get();

        return view('smart.navigate', [
            'places' => $places,
            'markers' => $places->map(fn (Place $p) => $p->toMap() + ['activities' => $p->activities->map(fn (Activity $a) => ['name' => $a->name, 'slug' => $a->slug])->all()])->values(),
        ]);
    }

    public function route(Request $request, RouteService $routes)
    {
        $data = $request->validate(['from' => 'required|exists:places,id', 'to' => 'required|exists:places,id|different:from']);

        return response()->json($routes->route(Place::findOrFail($data['from']), Place::findOrFail($data['to'])));
    }
}
