<?php

namespace App\Http\Controllers;

use App\Models\Park;
use App\Models\ViewLog;
use Illuminate\Http\Request;

class ParkController extends Controller
{
    public function show(Park $park)
    {
        abort_unless($park->is_published || auth()->user()?->isAdmin(), 404);

        ViewLog::record(park: $park);

        $park->load([
            'activities', 'scenes', 'media',
            'reviews' => fn ($q) => $q->where('is_approved', true)->with('user')->latest(),
            'packages' => fn ($q) => $q->where('is_active', true)->whereHas('operator', fn ($o) => $o->where('is_verified', true))->with('operator'),
            'operators' => fn ($q) => $q->where('is_verified', true),
        ]);

        return view('parks.show', [
            'park' => $park,
            'isFavourite' => auth()->check() && auth()->user()->favourites()->whereKey($park->id)->exists(),
            'nearby' => Park::published()->whereKeyNot($park->id)->get()
                ->sortBy(fn ($p) => ($p->latitude - $park->latitude) ** 2 + ($p->longitude - $park->longitude) ** 2)
                ->take(3),
        ]);
    }
}
