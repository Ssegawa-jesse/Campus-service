@extends('layouts.app')
@section('title', '360° Virtual Tour – '.$park->name)
@section('no_footer', true)
@section('no_flash', true)

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/pannellum/pannellum.css') }}">
@endpush

@section('content')
<div class="tour-wrap">
    @if ($scenes->isEmpty())
        <div class="h-100 d-grid text-white text-center" style="place-items:center">
            <div><i class="bi bi-camera fs-1"></i><h4 class="mt-2">360° scenes for {{ $park->name }} are coming soon</h4><a href="{{ route('parks.show', $park) }}" class="btn btn-gold mt-2">Back to park</a></div>
        </div>
    @else
        <div id="panorama"></div>
        <div class="tour-topbar d-flex gap-2">
            <a href="{{ route('parks.show', $park) }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> {{ Str::before($park->name, ' National Park') }}</a>
            <button id="motionBtn" class="btn btn-sm btn-gold d-md-none"><i class="bi bi-phone-flip"></i> Motion/VR mode</button>
            <button id="autoBtn" class="btn btn-sm btn-light"><i class="bi bi-pause-fill"></i> <span>Pause</span></button>
        </div>
        <div class="tour-caption" id="sceneCaption"></div>
        <div class="tour-sidebar p-3">
            <h6 class="text-gold mb-1"><i class="bi bi-badge-vr"></i> {{ $park->name }}</h6>
            <p class="small text-white-50">Drag to look around. Click the arrows to move between scenes and the <i class="bi bi-info-circle"></i> markers to learn more.</p>
            @foreach ($scenes as $scene)
                <button class="scene-btn" data-scene="scene-{{ $scene->id }}">
                    <img src="{{ $scene->imageUrl() }}" alt="" loading="lazy">
                    <span class="small">{{ $scene->title }}</span>
                </button>
            @endforeach
            <hr class="border-secondary">
            <label class="small text-white-50">Jump to another park</label>
            <select class="form-select form-select-sm" onchange="if(this.value) location=this.value">
                <option value="">Choose…</option>
                @foreach ($otherParks as $p)<option value="{{ route('tour.show', $p) }}">{{ $p->name }}</option>@endforeach
            </select>
            @auth
                @if (auth()->user()->isTourist())
                    <a href="{{ route('tourist.bookings.create', ['park' => $park->slug]) }}" class="btn btn-gold btn-sm w-100 mt-3">Book this park</a>
                @endif
            @else
                <a href="{{ route('planner.index') }}" class="btn btn-gold btn-sm w-100 mt-3">Plan a trip here</a>
            @endauth
        </div>
    @endif
</div>
@endsection

@if ($scenes->isNotEmpty())
@push('scripts')
@php $descriptions = $scenes->mapWithKeys(fn ($s) => ['scene-'.$s->id => ['title' => $s->title, 'text' => $s->description, 'id' => $s->id]]); @endphp
<script src="{{ asset('vendor/pannellum/pannellum.js') }}"></script>
<script>
const config = @json($config);
const descriptions = {!! Js::from($descriptions) !!};
const viewer = pannellum.viewer('panorama', config);
const caption = document.getElementById('sceneCaption');
const logged = new Set();

function onScene(id) {
    const d = descriptions[id];
    const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    caption.innerHTML = `<strong>${esc(d.title)}</strong>${d.text ? '<div class="small text-white-50">' + esc(d.text) + '</div>' : ''}`;
    document.querySelectorAll('.scene-btn').forEach(b => b.classList.toggle('active', b.dataset.scene === id));
    if (!logged.has(id)) { logged.add(id); P360.post(`{{ url('scenes') }}/${d.id}/view`); }
}
viewer.on('load', () => onScene(viewer.getScene()));
document.querySelectorAll('.scene-btn').forEach(b => b.addEventListener('click', () => viewer.loadScene(b.dataset.scene)));

let rotating = true;
document.getElementById('autoBtn').addEventListener('click', e => {
    rotating = !rotating;
    rotating ? viewer.startAutoRotate(-2) : viewer.stopAutoRotate();
    e.currentTarget.innerHTML = rotating ? '<i class="bi bi-pause-fill"></i> Pause' : '<i class="bi bi-play-fill"></i> Rotate';
});
document.getElementById('motionBtn').addEventListener('click', () => {
    viewer.isOrientationActive() ? viewer.stopOrientation() : viewer.startOrientation();
    viewer.toggleFullscreen();
});
</script>
@endpush
@endif
