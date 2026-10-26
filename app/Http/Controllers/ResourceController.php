<?php

namespace App\Http\Controllers;

use App\Models\DigitalResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $resources = DigitalResource::query()
            ->when($request->query('q'), function ($q, $term) {
                $q->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('course', 'like', "%{$term}%"));
            })
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->query('year'), fn ($q, $year) => $q->where('year', $year))
            ->orderByDesc('year')->orderBy('course')
            ->paginate(15)
            ->withQueryString();

        return view('resources.index', [
            'resources' => $resources,
            'years' => DigitalResource::whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }

    public function download(DigitalResource $resource)
    {
        abort_unless(Storage::disk('local')->exists($resource->file_path), 404, 'File not found.');

        $resource->increment('downloads');

        return Storage::disk('local')->download($resource->file_path, $resource->original_name ?: basename($resource->file_path));
    }
}
