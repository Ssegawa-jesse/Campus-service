<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Park;
use App\Models\ParkMedia;
use Illuminate\Http\Request;

/** Photo & video gallery management – upload many photos at once, MP4 videos, or YouTube links. */
class MediaController extends Controller
{
    public function index(Park $park)
    {
        return view('admin.media.index', ['park' => $park->load('media')]);
    }

    public function store(Request $request, Park $park)
    {
        $request->validate([
            'photos' => 'nullable|array|max:20',
            'photos.*' => 'image|max:8192',
            'video' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:102400',
            'youtube' => 'nullable|url|max:255',
            'caption' => 'nullable|string|max:200',
            'credit' => 'nullable|string|max:120',
        ]);

        $order = (int) $park->media()->max('sort_order');
        $count = 0;
        $meta = ['caption' => $request->input('caption'), 'credit' => $request->input('credit')];

        foreach ($request->file('photos', []) as $photo) {
            $park->media()->create($meta + ['type' => 'image', 'path' => $this->storeUpload($photo, 'gallery/'.$park->slug), 'sort_order' => ++$order]);
            $count++;
        }
        if ($request->hasFile('video')) {
            $park->media()->create($meta + ['type' => 'video', 'path' => $this->storeUpload($request->file('video'), 'videos'), 'sort_order' => ++$order]);
            $count++;
        }
        if ($request->filled('youtube')) {
            if (! youtube_embed($request->input('youtube'))) {
                return back()->withErrors(['youtube' => 'That does not look like a YouTube link.']);
            }
            $park->media()->create($meta + ['type' => 'youtube', 'path' => $request->input('youtube'), 'sort_order' => ++$order]);
            $count++;
        }

        return back()->with($count ? 'success' : 'error', $count ? "{$count} item(s) added to the gallery." : 'Choose photos, a video or a YouTube link first.');
    }

    public function update(Request $request, ParkMedia $media)
    {
        $data = $request->validate([
            'caption' => 'nullable|string|max:200',
            'credit' => 'nullable|string|max:120',
            'sort_order' => 'nullable|integer|min:0',
            'make_cover' => 'nullable|boolean',
        ]);

        $media->update(collect($data)->except('make_cover')->all());

        if ($request->boolean('make_cover') && $media->type === 'image') {
            $media->park->update(['cover_image' => $media->path]);
        }

        return back()->with('success', 'Gallery item updated.');
    }

    public function destroy(ParkMedia $media)
    {
        if ($media->type !== 'youtube' && $media->park->cover_image !== $media->path) {
            $this->deleteUpload($media->path);
        }
        $media->delete();

        return back()->with('success', 'Gallery item removed.');
    }
}
