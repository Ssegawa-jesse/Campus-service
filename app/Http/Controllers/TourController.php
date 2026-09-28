<?php

namespace App\Http\Controllers;

use App\Models\Park;
use App\Models\Scene;
use App\Models\ViewLog;

class TourController extends Controller
{
    public function show(Park $park)
    {
        abort_unless($park->is_published || auth()->user()?->isAdmin(), 404);

        $scenes = $park->scenes()->with('hotspots.target')->get();

        $config = [
            'default' => [
                'firstScene' => $scenes->isNotEmpty() ? 'scene-'.(request('scene') && $scenes->contains('id', (int) request('scene')) ? (int) request('scene') : $scenes->first()->id) : null,
                'sceneFadeDuration' => 900,
                'autoLoad' => true,
                'autoRotate' => -2,
                'compass' => true,
                'showControls' => true,
            ],
            'scenes' => $scenes->mapWithKeys(fn (Scene $s) => ['scene-'.$s->id => $s->toPannellum()])->all(),
        ];

        return view('tour.show', [
            'park' => $park,
            'scenes' => $scenes,
            'config' => $config,
            'otherParks' => Park::published()->whereKeyNot($park->id)->has('scenes')->orderBy('name')->get(),
        ]);
    }

    /** Called by the viewer (AJAX) each time a scene is opened – feeds the analytics dashboard. */
    public function logScene(Scene $scene)
    {
        ViewLog::record(scene: $scene);

        return response()->json(['ok' => true]);
    }
}
