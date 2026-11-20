<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Park;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ParkController extends Controller
{
    public function index()
    {
        return view('admin.parks.index', [
            'parks' => Park::withCount(['scenes', 'media', 'activities', 'bookings'])->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.parks.form', ['park' => new Park(['type' => 'national_park', 'difficulty_level' => 2, 'min_days' => 2, 'is_published' => true, 'latitude' => 1.3733, 'longitude' => 32.2903])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);
        if (Park::where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-'.Str::lower(Str::random(3));
        }
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->storeUpload($request->file('cover_image'), 'parks');
        }
        if ($request->hasFile('video_file')) {
            $data['video_url'] = $this->storeUpload($request->file('video_file'), 'videos');
        }

        $park = Park::create($data);

        return redirect()->route('admin.parks.edit', $park)->with('success', 'Park created. Now add activities, photos, videos and 360° scenes.');
    }

    public function edit(Park $park)
    {
        return view('admin.parks.form', ['park' => $park->load('activities')]);
    }

    public function update(Request $request, Park $park)
    {
        $data = $this->validated($request, $park);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->storeUpload($request->file('cover_image'), 'parks', $park->cover_image);
        }
        if ($request->hasFile('video_file')) {
            $data['video_url'] = $this->storeUpload($request->file('video_file'), 'videos', $park->video_url);
        }

        $park->update($data);

        return back()->with('success', 'Park updated.');
    }

    public function destroy(Park $park)
    {
        $this->deleteUpload($park->cover_image);
        foreach ($park->media as $m) {
            $this->deleteUpload($m->path);
        }
        foreach ($park->scenes as $s) {
            $this->deleteUpload($s->image_path);
        }
        $park->delete();

        return redirect()->route('admin.parks.index')->with('success', 'Park deleted.');
    }

    private function validated(Request $request, ?Park $park = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('parks')->ignore($park?->id)],
            'type' => ['required', Rule::in(array_keys(Park::TYPES))],
            'region' => 'required|string|max:60',
            'tagline' => 'nullable|string|max:200',
            'description' => 'required|string',
            'how_to_get_there' => 'nullable|string',
            'best_time' => 'nullable|string|max:120',
            'best_months' => 'nullable|array',
            'best_months.*' => 'integer|between:1,12',
            'interests' => 'nullable|array',
            'interests.*' => [Rule::in(array_keys(Park::INTERESTS))],
            'difficulty_level' => 'required|integer|between:1,3',
            'min_cost' => 'required|numeric|min:0',
            'min_days' => 'required|integer|min:1|max:30',
            'area_km2' => 'nullable|integer|min:0',
            'distance_from_kampala_km' => 'nullable|integer|min:0',
            'established' => 'nullable|integer|min:1900|max:2100',
            'latitude' => 'required|numeric|between:-2,5',
            'longitude' => 'required|numeric|between:29,36',
            'cover_image' => 'nullable|image|max:5120',
            'video_url' => 'nullable|url|max:255',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/webm|max:51200',
            'is_unesco' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ]);

        $data['best_months'] = array_map('intval', $data['best_months'] ?? []);
        $data['interests'] = $data['interests'] ?? [];
        $data['is_unesco'] = $request->boolean('is_unesco');
        $data['is_published'] = $request->boolean('is_published');
        unset($data['cover_image'], $data['video_file']);

        if (! $request->filled('video_url')) {
            unset($data['video_url']); // keep an uploaded video unless a link replaces it
        }

        return $data;
    }
}
