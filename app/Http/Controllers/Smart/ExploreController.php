<?php

namespace App\Http\Controllers\Smart;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Park;
use App\Models\Scene;

/** MODULE 6 – Virtual Exploration (secondary): HP5 360° previews with interactive hotspots. */
class ExploreController extends Controller
{
    public function index()
    {
        $park = Park::murchison();
        $scenes = Scene::where('park_id', $park->id)->where('is_published', true)->with('hotspots.target', 'hotspots.activity', 'activity')->orderBy('sort_order')->get();
        $start = request('scene') && $scenes->contains('id', (int) request('scene')) ? (int) request('scene')
            : (request('activity') ? $scenes->firstWhere('activity.slug', request('activity'))?->id : null);

        return view('smart.explore', [
            'park' => $park,
            'scenes' => $scenes,
            'start' => $start ?? $scenes->first()?->id,
            'activities' => Activity::active()->with(['media', 'scenes' => fn ($q) => $q->where('is_published', true)])->orderByDesc('priority')->get(),
            'data' => $scenes->mapWithKeys(fn (Scene $s) => ['scene-'.$s->id => [
                'id' => $s->id, 'title' => $s->title, 'description' => $s->description, 'image' => $s->imageUrl(),
                'pitch' => $s->initial_pitch, 'yaw' => $s->initial_yaw, 'camera' => $s->camera,
                'activity' => $s->activity ? ['name' => $s->activity->name, 'slug' => $s->activity->slug] : null,
                'hotspots' => $s->hotspots->map->toViewer()->values(),
            ]]),
        ]);
    }
}
