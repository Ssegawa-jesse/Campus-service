<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DigitalResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ResourceController extends Controller
{
    public function index()
    {
        return view('admin.resources.index', [
            'resources' => DigitalResource::with('uploader')->latest()->paginate(25),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'type' => ['required', Rule::in(array_keys(DigitalResource::TYPES))],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,epub,zip', 'max:20480'],
        ]);

        $file = $request->file('file');
        $resource = DigitalResource::create([
            'title' => $data['title'],
            'course' => $data['course'] ?? null,
            'year' => $data['year'] ?? null,
            'type' => $data['type'],
            'file_path' => $file->store('resources', 'local'),
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLog::record('resource.uploaded', "Uploaded \"{$resource->title}\"");

        return back()->with('success', 'Resource uploaded.');
    }

    public function destroy(DigitalResource $resource)
    {
        Storage::disk('local')->delete($resource->file_path);
        AuditLog::record('resource.deleted', "Deleted \"{$resource->title}\"");
        $resource->delete();

        return back()->with('success', 'Resource deleted.');
    }
}
