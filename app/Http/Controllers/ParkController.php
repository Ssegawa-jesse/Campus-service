<?php

namespace App\Http\Controllers;

use App\Models\Park;
use App\Models\ViewLog;
use Illuminate\Http\Request;

class ParkController extends Controller
{
    public function index(Request $request)
    {
        $query = Park::published()->withCount(['scenes', 'activities'])
            ->withAvg(['reviews' => fn ($q) => $q->where('is_approved', true)], 'rating');

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('region', 'like', "%{$search}%"));
        }
        if ($region = $request->input('region')) {
            $query->where('region', $region);
        }
        if ($interest = $request->input('interest')) {
            $query->whereJsonContains('interests', $interest);
        }
        if ($difficulty = $request->integer('difficulty')) {
            $query->where('difficulty_level', $difficulty);
        }

        $sort = $request->input('sort', 'name');
        match ($sort) {
            'popular' => $query->orderByDesc('views_count'),
            'cost' => $query->orderBy('min_cost'),
            'distance' => $query->orderBy('distance_from_kampala_km'),
            default => $query->orderBy('name'),
        };

        return view('parks.index', [
            'parks' => $query->get(),
            'regions' => Park::published()->distinct()->orderBy('region')->pluck('region'),
        ]);
    }

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

    public function compare(Request $request)
    {
        $all = Park::published()->orderBy('name')->get(['id', 'name', 'slug']);
        $ids = collect($request->input('parks', []))->filter()->unique()->take(4)->values();

        if ($ids->isEmpty()) {
            $ids = $all->take(2)->pluck('id');
        }

        $parks = Park::published()->whereIn('id', $ids)->with('activities')->withCount('scenes')
            ->withAvg(['reviews' => fn ($q) => $q->where('is_approved', true)], 'rating')->get();

        return view('parks.compare', ['all' => $all, 'parks' => $parks, 'selected' => $ids->all()]);
    }
}
