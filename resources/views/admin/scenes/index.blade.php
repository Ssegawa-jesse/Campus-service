@extends('layouts.app')
@section('title', 'HP5 360° Media & Hotspot Studio')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1><i class="bi bi-badge-vr"></i> HP5 360° Media & Hotspot Studio</h1><a href="{{ route('explore') }}" target="_blank" class="btn btn-outline-forest"><i class="bi bi-play"></i> Public preview</a></div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
    <h5>Upload an HP5 360° panorama</h5>
    <p class="small text-muted mb-3">Export the HP5 360 VR capture as an <strong>equirectangular JPG</strong> (width = 2 × height, e.g. 5760×2880). For fast loading, 4096×2048 at ~80% quality is ideal. Max 40 MB. Link it to the activity it previews. After uploading, click inside the panorama to add hotspots.</p>
    <form method="POST" action="{{ route('admin.scenes.store', $park) }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-md-4"><label class="form-label small">Scene title</label><input name="title" class="form-control" placeholder="e.g. Base of the falls from the launch" required></div>
        <div class="col-md-4"><label class="form-label small">Activity</label><select name="activity_id" class="form-select"><option value="">— General park scene —</option>@foreach ($activities as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label small">Equirectangular image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label small">Camera</label><input name="camera" class="form-control" value="HP5 360 VR"></div>
        <div class="col-md-4"><label class="form-label small">Captured on</label><input type="date" name="captured_on" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small">Description</label><input name="description" class="form-control" placeholder="Shown under the viewer"></div>
        <div class="col-12"><button class="btn btn-gold"><i class="bi bi-upload"></i> Upload & open in hotspot editor</button></div>
    </form>
</div></div>

@foreach ($park->scenes->groupBy(fn ($s) => $s->activity?->name ?? 'General park scenes') as $group => $scenes)
    <h5 class="mt-3">{{ $group }}</h5>
    <div class="row g-3 mb-2">
        @foreach ($scenes as $scene)
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <img src="{{ $scene->imageUrl() }}" class="card-img-top" style="height:140px;object-fit:cover" alt="">
                    <div class="card-body">
                        <h6 class="mb-1">{{ $scene->title }} @unless ($scene->is_published)<span class="badge bg-secondary">hidden</span>@endunless</h6>
                        <div class="small text-muted mb-2">{{ $scene->camera }}{{ $scene->captured_on ? ' · '.$scene->captured_on->format('j M Y') : '' }}<br>{{ $scene->hotspots->count() }} hotspots · {{ $scene->views_count }} views</div>
                        <a href="{{ route('admin.scenes.edit', $scene) }}" class="btn btn-sm btn-forest">Edit & hotspots</a>
                        <a href="{{ route('explore', ['scene' => $scene->id]) }}" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach

<div class="card border-0 shadow-sm mt-4"><div class="card-body small">
    <h6>Standard activity photos & videos</h6>
    <p class="mb-2">Upload photos, MP4 videos or YouTube links for each activity under <a href="{{ route('admin.media.index', $park) }}">Park gallery (photos & videos)</a>. Choose the activity there so the media appears with that activity on the 360° Explore page.</p>
</div></div>
@endsection
