@extends('layouts.app')
@section('title', 'Edit scene')
@section('dashboard', true)

@push('styles')<link rel="stylesheet" href="{{ asset('vendor/pannellum/pannellum.css') }}">@endpush

@section('content')
<div class="page-head"><h1>{{ $scene->title }}</h1><a href="{{ route('admin.scenes.index', $scene->park) }}" class="btn btn-link">← All scenes</a></div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <div id="editorPano"></div>
    <p class="small text-muted mt-2 mb-0"><i class="bi bi-cursor"></i> <strong>Click anywhere in the panorama</strong> to capture its position for a new hotspot. Current view: pitch <span id="curPitch">0</span>, yaw <span id="curYaw">0</span>. <button type="button" class="btn btn-sm btn-link p-0" id="setStart">Use current view as starting view</button></p>
</div></div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h5>Add hotspot</h5>
            <form method="POST" action="{{ route('admin.hotspots.store', $scene) }}" class="row g-2">
                @csrf
                <div class="col-6"><label class="form-label small">Pitch</label><input name="pitch" id="hsPitch" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small">Yaw</label><input name="yaw" id="hsYaw" class="form-control form-control-sm" required></div>
                <div class="col-12">
                    <select name="type" id="hsType" class="form-select form-select-sm">
                        <option value="info">Information point</option>
                        <option value="scene" @disabled($others->isEmpty())>Link to another scene</option>
                    </select>
                </div>
                <div class="col-12 d-none" id="targetWrap"><select name="target_scene_id" class="form-select form-select-sm">@foreach ($others as $o)<option value="{{ $o->id }}">{{ $o->title }}</option>@endforeach</select></div>
                <div class="col-12"><input name="title" class="form-control form-control-sm" placeholder="Label, e.g. Silverback resting spot"></div>
                <div class="col-12"><textarea name="info_text" rows="2" class="form-control form-control-sm" placeholder="Information text"></textarea></div>
                <div class="col-12"><button class="btn btn-sm btn-gold">Add hotspot</button></div>
            </form>
            <hr>
            <h6>Existing hotspots</h6>
            @forelse ($scene->hotspots as $h)
                <div class="d-flex justify-content-between align-items-center small border-bottom py-1">
                    <span><i class="bi bi-{{ $h->type === 'scene' ? 'arrow-up-right-circle' : 'info-circle' }}"></i> {{ $h->title ?: $h->target?->title }} <span class="text-muted">({{ $h->pitch }}, {{ $h->yaw }})</span></span>
                    <form method="POST" action="{{ route('admin.hotspots.destroy', $h) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x-lg"></i></button></form>
                </div>
            @empty
                <p class="small text-muted">None yet.</p>
            @endforelse
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h5>Scene details</h5>
            <form method="POST" action="{{ route('admin.scenes.update', $scene) }}" enctype="multipart/form-data" class="row g-2">
                @csrf @method('PUT')
                <div class="col-12"><input name="title" value="{{ $scene->title }}" class="form-control" required></div>
                <div class="col-12"><textarea name="description" rows="3" class="form-control">{{ $scene->description }}</textarea></div>
                <div class="col-4"><label class="form-label small">Start pitch</label><input name="initial_pitch" id="startPitch" value="{{ $scene->initial_pitch }}" class="form-control form-control-sm"></div>
                <div class="col-4"><label class="form-label small">Start yaw</label><input name="initial_yaw" id="startYaw" value="{{ $scene->initial_yaw }}" class="form-control form-control-sm"></div>
                <div class="col-4"><label class="form-label small">Order</label><input name="sort_order" value="{{ $scene->sort_order }}" class="form-control form-control-sm"></div>
                <div class="col-12"><label class="form-label small">Replace panorama image</label><input type="file" name="image" accept="image/*" class="form-control form-control-sm"></div>
                <div class="col-12"><button class="btn btn-forest btn-sm">Save scene</button></div>
            </form>
            <form method="POST" action="{{ route('admin.scenes.destroy', $scene) }}" class="mt-3" data-confirm="Delete this scene?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete scene</button></form>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
@php $hotspotPreview = $scene->hotspots->map(fn ($h) => ['pitch' => $h->pitch, 'yaw' => $h->yaw, 'type' => 'info', 'text' => $h->title ?: ($h->target?->title ?? 'Hotspot')])->values(); @endphp
<script src="{{ asset('vendor/pannellum/pannellum.js') }}"></script>
<script>
const v = pannellum.viewer('editorPano', {
    type: 'equirectangular', panorama: @json($scene->imageUrl()), autoLoad: true,
    pitch: {{ $scene->initial_pitch }}, yaw: {{ $scene->initial_yaw }},
    hotSpots: {!! Js::from($hotspotPreview) !!},
});
v.on('mouseup', e => {
    const [p, y] = v.mouseEventToCoords(e);
    document.getElementById('hsPitch').value = p.toFixed(2);
    document.getElementById('hsYaw').value = y.toFixed(2);
});
setInterval(() => { curPitch.textContent = v.getPitch().toFixed(1); curYaw.textContent = v.getYaw().toFixed(1); }, 300);
document.getElementById('setStart').addEventListener('click', () => { startPitch.value = v.getPitch().toFixed(2); startYaw.value = v.getYaw().toFixed(2); });
const t = document.getElementById('hsType'), tw = document.getElementById('targetWrap');
t.addEventListener('change', () => tw.classList.toggle('d-none', t.value !== 'scene'));
</script>
@endpush
