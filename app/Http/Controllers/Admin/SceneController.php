<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Hotspot;
use App\Models\Park;
use App\Models\Scene;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * HP5 360° MEDIA & HOTSPOT STUDIO (researcher/admin).
 * Upload equirectangular (2:1) panoramas captured with the HP5 360 VR camera, link each to a
 * Murchison Falls activity, click anywhere in the panorama to place hotspots (title, description,
 * audio/text field notes, "Add to Smart Itinerary" activity link) and edit or delete them any time.
 */
class SceneController extends Controller
{
    public function studio()
    {
        $park = Park::murchison()->load(['scenes.hotspots', 'scenes.activity']);

        return view('admin.scenes.index', ['park' => $park, 'activities' => Activity::orderBy('name')->get()]);
    }

    public function index(Park $park)
    {
        return redirect()->route('admin.studio');
    }

    public function store(Request $request, Park $park)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'image' => 'required|image|max:20480',
        ]);

        $scene = $park->scenes()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'image_path' => $this->storeUpload($request->file('image'), 'scenes/'.$park->slug),
            'sort_order' => (int) $park->scenes()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.scenes.edit', $scene)->with('success', 'Scene uploaded. Click in the panorama to add hotspots.');
    }

    public function edit(Scene $scene)
    {
        return view('admin.scenes.edit', [
            'scene' => $scene->load('hotspots.target', 'hotspots.activity', 'park', 'activity'),
            'others' => $scene->park->scenes()->whereKeyNot($scene->id)->get(),
            'activities' => Activity::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Scene $scene)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'initial_pitch' => 'nullable|numeric|between:-90,90',
            'initial_yaw' => 'nullable|numeric|between:-180,180',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:20480',
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeUpload($request->file('image'), 'scenes/'.$scene->park->slug, $scene->image_path);
        }
        unset($data['image']);

        $scene->update(array_filter($data, fn ($v) => $v !== null) + ['description' => $data['description'] ?? null, 'activity_id' => $data['activity_id']]);

        return back()->with('success', 'Scene updated.');
    }

    public function destroy(Scene $scene)
    {
        $park = $scene->park;
        $this->deleteUpload($scene->image_path);
        $scene->delete();

        return redirect()->route('admin.studio')->with('success', 'Scene deleted.');
    }

    public function storeHotspot(Request $request, Scene $scene)
    {
        $data = $this->hotspotData($request, $scene);
        if ($request->hasFile('audio')) {
            $data['audio_path'] = $this->storeUpload($request->file('audio'), 'audio/'.$scene->id);
        }
        $scene->hotspots()->create($data);

        return back()->with('success', 'Hotspot added.');
    }

    public function updateHotspot(Request $request, Hotspot $hotspot)
    {
        $data = $this->hotspotData($request, $hotspot->scene);
        if ($request->hasFile('audio')) {
            $data['audio_path'] = $this->storeUpload($request->file('audio'), 'audio/'.$hotspot->scene_id, $hotspot->audio_path);
        } elseif ($request->boolean('remove_audio')) {
            $this->deleteUpload($hotspot->audio_path);
            $data['audio_path'] = null;
        }
        $hotspot->update($data);

        return back()->with('success', 'Hotspot updated.');
    }

    private function hotspotData(Request $request, Scene $scene): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['scene', 'info'])],
            'pitch' => 'required|numeric|between:-90,90',
            'yaw' => 'required|numeric|between:-180,180',
            'title' => 'nullable|string|max:120',
            'info_text' => 'nullable|string|max:2000',
            'note' => 'nullable|string|max:2000',
            'activity_id' => 'nullable|exists:activities,id',
            'audio' => 'nullable|file|mimes:mp3,m4a,wav,ogg,aac|max:20480',
            'target_scene_id' => ['required_if:type,scene', 'nullable', Rule::exists('scenes', 'id')->where('park_id', $scene->park_id)],
        ]);
        unset($data['audio']);
        $data['activity_id'] ??= null;
        if ($data['type'] !== 'scene') {
            $data['target_scene_id'] = null;
        }

        return $data;
    }

    public function destroyHotspot(Hotspot $hotspot)
    {
        $this->deleteUpload($hotspot->audio_path);
        $hotspot->delete();

        return back()->with('success', 'Hotspot removed.');
    }
}
