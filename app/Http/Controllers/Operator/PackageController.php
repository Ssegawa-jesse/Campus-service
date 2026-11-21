<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Park;
use App\Models\TourPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        return view('operator.packages.index', [
            'packages' => $request->user()->operator->packages()->with('park')->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('operator.packages.form', ['package' => new TourPackage(['days' => 3, 'max_group' => 6, 'is_active' => true]), 'parks' => Park::where('slug', config('murchison.park_slug'))->get()]);
    }

    public function store(Request $request)
    {
        $operator = $request->user()->operator;
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(5));

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUpload($request->file('image'), 'packages');
        }

        $operator->packages()->create($data);

        return redirect()->route('operator.packages.index')->with('success', 'Package created.');
    }

    public function edit(Request $request, TourPackage $package)
    {
        $this->own($request, $package);

        return view('operator.packages.form', ['package' => $package, 'parks' => Park::where('slug', config('murchison.park_slug'))->get()]);
    }

    public function update(Request $request, TourPackage $package)
    {
        $this->own($request, $package);
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUpload($request->file('image'), 'packages', $package->image);
        }

        $package->update($data);

        return redirect()->route('operator.packages.index')->with('success', 'Package updated.');
    }

    public function destroy(Request $request, TourPackage $package)
    {
        $this->own($request, $package);
        $this->deleteUpload($package->image);
        $package->delete();

        return back()->with('success', 'Package deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'park_id' => 'required|exists:parks,id',
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'includes' => 'nullable|string|max:2000',
            'days' => 'required|integer|min:1|max:30',
            'price_ugx' => 'required|integer|min:1000',
            'max_group' => 'required|integer|min:1|max:100',
            'image' => 'nullable|image|max:8192',
            'video' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:51200',
            'video_url' => 'nullable|url|max:255',
            'slots_available' => 'required|integer|min:0|max:500',
            'available_from' => 'nullable|date',
            'available_to' => 'nullable|date|after_or_equal:available_from',
            'is_active' => 'nullable|boolean',
        ]);
        unset($data['video']);
        if ($request->hasFile('video')) {
            $data['video_url'] = $this->storeUpload($request->file('video'), 'packages/videos');
        } elseif (empty($data['video_url'])) {
            unset($data['video_url']); // keep an already uploaded video
        }
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);

        return $data;
    }

    private function own(Request $request, TourPackage $package): void
    {
        abort_unless($package->operator_id === $request->user()->operator->id, 403);
    }
}
