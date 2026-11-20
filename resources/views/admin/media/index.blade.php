@extends('layouts.app')
@section('title', 'Gallery – '.$park->name)
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Photos & videos · {{ $park->name }}</h1><a href="{{ route('admin.parks.edit', $park) }}" class="btn btn-link">← Back to park</a></div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
    <h5>Add to gallery</h5>
    <form method="POST" action="{{ route('admin.media.store', $park) }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-md-4"><label class="form-label">Photos <small class="text-muted">(select many, max 8 MB each)</small></label><input type="file" name="photos[]" accept="image/*" multiple class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Video file <small class="text-muted">(MP4, max 100 MB)</small></label><input type="file" name="video" accept="video/*" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">…or YouTube link</label><input name="youtube" class="form-control" placeholder="https://youtu.be/…"></div>
        <div class="col-md-6"><input name="caption" class="form-control" placeholder="Caption (optional)"></div>
        <div class="col-md-4"><input name="credit" class="form-control" placeholder="Photo credit, e.g. UWA / Group 12"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-gold"><i class="bi bi-upload"></i> Upload</button></div>
    </form>
    <p class="small text-muted mt-2 mb-0"><i class="bi bi-lightbulb"></i> Tip: compress photos to under 500 KB (e.g. with squoosh.app) so pages load quickly on mobile data. Large video uploads may need <code>upload_max_filesize</code> and <code>post_max_size</code> raised in php.ini.</p>
</div></div>

<div class="row g-3">
    @forelse ($park->media as $m)
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                @if ($m->type === 'image')
                    <img src="{{ $m->url() }}" class="card-img-top" style="height:170px;object-fit:cover" alt="">
                @elseif ($m->type === 'youtube')
                    <div class="ratio ratio-16x9"><iframe src="{{ $m->embedUrl() }}" allowfullscreen title="video"></iframe></div>
                @else
                    <div class="ratio ratio-16x9"><video src="{{ $m->url() }}" controls preload="metadata"></video></div>
                @endif
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.media.update', $m) }}" class="row g-1">
                        @csrf @method('PUT')
                        <div class="col-12"><input name="caption" value="{{ $m->caption }}" class="form-control form-control-sm" placeholder="Caption"></div>
                        <div class="col-8"><input name="credit" value="{{ $m->credit }}" class="form-control form-control-sm" placeholder="Credit"></div>
                        <div class="col-4"><input type="number" name="sort_order" value="{{ $m->sort_order }}" class="form-control form-control-sm" title="Order"></div>
                        @if ($m->type === 'image')<div class="col-12 form-check ms-1"><input type="checkbox" name="make_cover" value="1" class="form-check-input" id="c{{ $m->id }}" @checked($park->cover_image === $m->path)><label for="c{{ $m->id }}" class="form-check-label small">Use as cover photo</label></div>@endif
                        <div class="col-12 d-flex gap-1 mt-1"><button class="btn btn-sm btn-outline-forest flex-fill">Save</button>
                    </form>
                    <form method="POST" action="{{ route('admin.media.destroy', $m) }}" data-confirm="Remove this item?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></div>
                </div>
            </div>
        </div>
    @empty
        <p class="text-muted">No photos or videos yet.</p>
    @endforelse
</div>
@endsection
