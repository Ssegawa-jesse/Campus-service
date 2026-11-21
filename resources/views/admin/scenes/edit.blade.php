@extends('layouts.app')
@section('title', 'Edit scene')
@section('dashboard', true)

@push('styles')<link rel="stylesheet" href="{{ asset('vendor/pannellum/pannellum.css') }}">@endpush

@section('content')
<div class="page-head"><h1>{{ $scene->title }}</h1><a href="{{ route('admin.studio') }}" class="btn btn-link">← HP5 360° Studio</a></div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <div id="editorPano"></div>
    <p class="small text-muted mt-2 mb-0"><i class="bi bi-cursor"></i> <strong>Click anywhere in the panorama</strong> to capture its position for a new hotspot. Current view: pitch <span id="curPitch">0</span>, yaw <span id="curYaw">0</span>. <button type="button" class="btn btn-sm btn-link p-0" id="setStart">Use current view as starting view</button></p>
</div></div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h5>Add hotspot</h5>
            <form method="POST" action="{{ route('admin.hotspots.store', $scene) }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-6"><label class="form-label small">Pitch</label><input name="pitch" id="hsPitch" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small">Yaw</label><input name="yaw" id="hsYaw" class="form-control form-control-sm" required></div>
                <div class="col-12">
                    <select name="type" id="hsType" class="form-select form-select-sm">
                        <option value="info">Information / activity point</option>
                        <option value="scene" @disabled($others->isEmpty())>Link to another scene</option>
                    </select>
                </div>
                <div class="col-12 d-none" id="targetWrap"><select name="target_scene_id" class="form-select form-select-sm">@foreach ($others as $o)<option value="{{ $o->id }}">{{ $o->title }}</option>@endforeach</select></div>
                <div class="col-12"><input name="title" class="form-control form-control-sm" placeholder="Label, e.g. Silverback resting spot"></div>
                <div class="col-12"><textarea name="info_text" rows="2" class="form-control form-control-sm" placeholder="Description shown to tourists"></textarea></div>
                <div class="col-12"><textarea name="note" rows="2" class="form-control form-control-sm" placeholder="Researcher field note (private)"></textarea></div>
                <div class="col-12"><label class="form-label small mb-0">"Add to Smart Itinerary" button for</label><select name="activity_id" class="form-select form-select-sm"><option value="">— none —</option>@foreach ($activities as $a)<option value="{{ $a->id }}" @selected($scene->activity_id === $a->id)>{{ $a->name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label small mb-0">Audio note (mp3/m4a/wav, optional)</label><input type="file" name="audio" accept="audio/*" class="form-control form-control-sm"></div>
                <div class="col-12"><button class="btn btn-sm btn-gold">Add hotspot</button></div>
            </form>
            <hr>
            <h6>Existing hotspots</h6>
            @forelse ($scene->hotspots as $h)
                <details class="border-bottom py-1 small">
                    <summary class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-{{ $h->type === 'scene' ? 'arrow-up-right-circle' : 'info-circle' }}"></i> {{ $h->title ?: $h->target?->title }} <span class="text-muted">({{ $h->pitch }}, {{ $h->yaw }})</span>
                            @if ($h->activity)<span class="badge bg-danger-subtle text-danger">+ itinerary</span>@endif @if ($h->audio_path)<i class="bi bi-music-note-beamed"></i>@endif</span>
                        <span class="d-flex gap-2"><span class="text-primary">Edit</span>
                        <form method="POST" action="{{ route('admin.hotspots.destroy', $h) }}" data-confirm="Delete this hotspot?">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button></form></span>
                    </summary>
                    <form method="POST" action="{{ route('admin.hotspots.update', $h) }}" enctype="multipart/form-data" class="row g-1 my-2">@csrf @method('PUT')
                        <input type="hidden" name="type" value="{{ $h->type }}">
                        @if ($h->type === 'scene')<input type="hidden" name="target_scene_id" value="{{ $h->target_scene_id }}">@endif
                        <div class="col-6"><input name="pitch" value="{{ $h->pitch }}" class="form-control form-control-sm"></div>
                        <div class="col-6"><input name="yaw" value="{{ $h->yaw }}" class="form-control form-control-sm"></div>
                        <div class="col-12"><input name="title" value="{{ $h->title }}" class="form-control form-control-sm" placeholder="Title"></div>
                        <div class="col-12"><textarea name="info_text" rows="2" class="form-control form-control-sm" placeholder="Description">{{ $h->info_text }}</textarea></div>
                        <div class="col-12"><textarea name="note" rows="2" class="form-control form-control-sm" placeholder="Field note">{{ $h->note }}</textarea></div>
                        <div class="col-12"><select name="activity_id" class="form-select form-select-sm"><option value="">— no itinerary button —</option>@foreach ($activities as $a)<option value="{{ $a->id }}" @selected($h->activity_id === $a->id)>{{ $a->name }}</option>@endforeach</select></div>
                        <div class="col-12"><input type="file" name="audio" accept="audio/*" class="form-control form-control-sm">
                            @if ($h->audio_path)<audio src="{{ media_url($h->audio_path) }}" controls class="w-100 mt-1"></audio><label class="small"><input type="checkbox" name="remove_audio" value="1"> remove audio</label>@endif</div>
                        <div class="col-12"><button class="btn btn-sm btn-forest">Save hotspot</button></div>
                    </form>
                </details>
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
                <div class="col-md-6"><label class="form-label small">Activity</label><select name="activity_id" class="form-select form-select-sm"><option value="">— General —</option>@foreach ($activities as $a)<option value="{{ $a->id }}" @selected($scene->activity_id === $a->id)>{{ $a->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label small">Camera</label><input name="camera" value="{{ $scene->camera }}" class="form-control form-control-sm"></div>
                <div class="col-md-6"><label class="form-label small">Captured on</label><input type="date" name="captured_on" value="{{ $scene->captured_on?->toDateString() }}" class="form-control form-control-sm"></div>
                <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_published" value="1" id="pub" @checked($scene->is_published)><label for="pub" class="form-check-label small">Published to tourists</label></div></div>
                <div class="col-12"><label class="form-label small">Replace panorama image (HP5 equirectangular)</label><input type="file" name="image" accept="image/*" class="form-control form-control-sm"></div>
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
