@extends('layouts.app')
@section('title', '360° scenes – '.$park->name)
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>360° scenes · {{ $park->name }}</h1><div><a href="{{ route('tour.show', $park) }}" target="_blank" class="btn btn-outline-forest"><i class="bi bi-play"></i> Preview tour</a> <a href="{{ route('admin.parks.edit', $park) }}" class="btn btn-link">← Park</a></div></div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
    <h5>Upload a new 360° panorama</h5>
    <p class="small text-muted">Use an <strong>equirectangular</strong> image (width = 2 × height, e.g. 6000×3000) from a 360° camera (Insta360, Ricoh Theta) or a phone panorama app (Google Street View / Cardboard Camera). Max 20 MB – for fast loading, 4096×2048 JPG at ~80% quality is ideal.</p>
    <form method="POST" action="{{ route('admin.scenes.store', $park) }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-md-4"><input name="title" class="form-control" placeholder="Scene title, e.g. Top of the falls" required></div>
        <div class="col-md-4"><input type="file" name="image" accept="image/*" class="form-control" required></div>
        <div class="col-md-4 d-grid"><button class="btn btn-gold"><i class="bi bi-upload"></i> Upload scene</button></div>
        <div class="col-12"><input name="description" class="form-control" placeholder="Short description shown during the tour"></div>
    </form>
</div></div>

<div class="row g-3">
    @forelse ($park->scenes as $scene)
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <img src="{{ $scene->imageUrl() }}" class="card-img-top" style="height:140px;object-fit:cover" alt="">
                <div class="card-body">
                    <h6 class="mb-1">{{ $scene->title }}</h6>
                    <div class="small text-muted mb-2">{{ $scene->hotspots->count() }} hotspots · {{ $scene->views_count }} views · order {{ $scene->sort_order }}</div>
                    <a href="{{ route('admin.scenes.edit', $scene) }}" class="btn btn-sm btn-forest">Edit & hotspots</a>
                    <a href="{{ route('tour.show', ['park' => $park, 'scene' => $scene->id]) }}" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                </div>
            </div>
        </div>
    @empty
        <p class="text-muted">No scenes yet.</p>
    @endforelse
</div>
@endsection
