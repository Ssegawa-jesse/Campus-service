<?php

namespace App\Http\Controllers;

use App\Models\Park;
use App\Models\ViewLog;

/** Park profile – the platform is scoped to Murchison Falls National Park only. */
class ParkController extends Controller
{
    public function murchison()
    {
        return $this->show(Park::murchison());
    }

    public function show(Park $park)
    {
        abort_unless($park->slug === config('murchison.park_slug') && ($park->is_published || auth()->user()?->isAdmin()), 404);

        ViewLog::record(park: $park);

        $park->load([
            'activities' => fn ($q) => $q->with('place')->orderByDesc('priority'), 'scenes', 'media',
            'reviews' => fn ($q) => $q->where('is_approved', true)->with('user')->latest(),
            'packages' => fn ($q) => $q->where('is_active', true)->whereHas('operator', fn ($o) => $o->where('is_verified', true))->with('operator'),
            'operators' => fn ($q) => $q->where('is_verified', true),
        ]);

        return view('parks.show', [
            'park' => $park,
            'isFavourite' => auth()->check() && auth()->user()->favourites()->whereKey($park->id)->exists(),
        ]);
    }
}
