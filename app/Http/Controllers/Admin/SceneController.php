<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotspot;
use App\Models\Park;
use App\Models\Scene;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** 360° scene & hotspot editor. Click inside the preview to place a hotspot. */
class SceneController extends Controller
{
    public function index(Park $park)
    {
        return view('admin.scenes.index', ['park' => $park->load('scenes.hotspots')]);
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
            'scene' => $scene->load('hotspots.target', 'park'),
            'others' => $scene->park->scenes()->whereKeyNot($scene->id)->get(),
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

        $scene->update(array_filter($data, fn ($v) => $v !== null) + ['description' => $data['description'] ?? null]);

        return back()->with('success', 'Scene updated.');
    }

    public function destroy(Scene $scene)
    {
        $park = $scene->park;
        $this->deleteUpload($scene->image_path);
        $scene->delete();

        return redirect()->route('admin.scenes.index', $park)->with('success', 'Scene deleted.');
    }

    public function storeHotspot(Request $request, Scene $scene)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['scene', 'info'])],
            'pitch' => 'required|numeric|between:-90,90',
            'yaw' => 'required|numeric|between:-180,180',
            'title' => 'nullable|string|max:120',
            'info_text' => 'nullable|string|max:1000',
            'target_scene_id' => ['required_if:type,scene', 'nullable', Rule::exists('scenes', 'id')->where('park_id', $scene->park_id)],
        ]);

        $scene->hotspots()->create($data);

        return back()->with('success', 'Hotspot added.');
    }

    public function destroyHotspot(Hotspot $hotspot)
    {
        $hotspot->delete();

        return back()->with('success', 'Hotspot removed.');
    }
}
