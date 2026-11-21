@extends('layouts.app')
@section('title', '360° Virtual Exploration')

@push('styles')<link rel="stylesheet" href="{{ asset('vendor/pannellum/pannellum.css') }}">@endpush

@section('content')
<section class="bg-forest py-4">
    <div class="container-xl">
        <div class="section-kicker text-gold">Module 6 · Secondary pre-trip preview</div>
        <h1 class="fw-bold mb-1">360° Virtual Exploration</h1>
        <p class="text-white-50 mb-0">Preview each Murchison Falls activity in 360°. The panoramas were captured on an HP5 360 VR camera during MUBS field research. Tap a hotspot to read notes, play audio, or add the activity to your Smart Itinerary.</p>
    </div>
</section>
<div class="container-xl py-4">
    @if ($scenes->isEmpty())
        <div class="alert alert-info">No 360° scenes are published yet.</div>
    @else
    <div class="row g-4">
        <div class="col-lg-9">
            <div class="explore-wrap shadow-sm">
                <div id="explorePano"></div>
                <div id="hsPanel" class="hs-panel card shadow d-none"><div class="card-body">
                    <button type="button" class="btn-close float-end" aria-label="Close" id="hsClose"></button>
                    <h6 id="hsTitle" class="mb-2"></h6>
                    <p id="hsText" class="small mb-2"></p>
                    <audio id="hsAudio" controls class="w-100 d-none mb-2"></audio>
                    <button id="hsAdd" class="btn btn-gold btn-sm w-100 d-none"><i class="bi bi-calendar-plus"></i> Add to Smart Itinerary</button>
                    <div id="hsMsg" class="small text-success mt-2"></div>
                </div></div>
            </div>
            <div id="sceneInfo" class="mt-2 small text-muted"></div>
        </div>
        <div class="col-lg-3">
            <div class="list-group shadow-sm">
                @foreach ($scenes as $s)
                    <button class="list-group-item list-group-item-action scene-pick d-flex gap-2 align-items-center" data-scene="scene-{{ $s->id }}">
                        <img src="{{ $s->imageUrl() }}" width="64" height="32" style="object-fit:cover;border-radius:4px" alt="" loading="lazy">
                        <span class="small"><strong>{{ $s->activity?->name ?? $s->title }}</strong><br><span class="text-muted">{{ $s->title }}</span></span>
                    </button>
                @endforeach
            </div>
            <a href="{{ route('itinerary.index') }}" class="btn btn-outline-forest w-100 mt-3"><i class="bi bi-calendar2-week"></i> Go to my itinerary (<span id="cartCount">{{ count(session('itinerary_must_do', [])) }}</span>)</a>
        </div>
    </div>
    @endif

    <h4 class="mt-5 mb-3">Activity photos & videos</h4>
    <div class="row g-3">
        @foreach ($activities as $a)
            @foreach ($a->media as $m)
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        @if ($m->type === 'image')<img src="{{ $m->url() }}" class="card-img-top" style="height:160px;object-fit:cover" alt="{{ $m->caption }}" loading="lazy">
                        @elseif ($m->type === 'video')<video src="{{ $m->url() }}" class="card-img-top" style="height:160px" controls preload="metadata"></video>
                        @elseif ($m->embedUrl())<div class="ratio ratio-16x9"><iframe src="{{ $m->embedUrl() }}" title="{{ $m->caption }}" allowfullscreen loading="lazy"></iframe></div>@endif
                        <div class="card-body small"><strong>{{ $a->name }}</strong><div class="text-muted">{{ $m->caption }}</div></div>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
</div>
@endsection

@if ($scenes->isNotEmpty())
@push('scripts')
<script src="{{ asset('vendor/pannellum/pannellum.js') }}"></script>
<script>
(() => {
    const data = {!! Js::from($data) !!};
    const addUrl = @json(url('itinerary/add'));
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const panel = document.getElementById('hsPanel'), add = document.getElementById('hsAdd'), audio = document.getElementById('hsAudio'), msg = document.getElementById('hsMsg');
    let current = null;

    function openPanel(h) {
        document.getElementById('hsTitle').textContent = h.title || '';
        document.getElementById('hsText').textContent = h.text || '';
        msg.textContent = '';
        if (h.audio) { audio.src = h.audio; audio.classList.remove('d-none'); } else { audio.pause(); audio.classList.add('d-none'); }
        current = h.activity;
        add.classList.toggle('d-none', !current);
        if (current) add.innerHTML = '<i class="bi bi-calendar-plus"></i> Add “' + current.name.replace(/[<>&]/g, '') + '” to Smart Itinerary';
        panel.classList.remove('d-none');
    }
    document.getElementById('hsClose').addEventListener('click', () => { panel.classList.add('d-none'); audio.pause(); });
    add.addEventListener('click', async () => {
        const r = await fetch(`${addUrl}/${current.slug}`, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
        const d = await r.json();
        msg.textContent = d.message || 'Added.';
        document.getElementById('cartCount').textContent = d.count ?? '';
    });

    const scenes = {};
    Object.entries(data).forEach(([key, s]) => {
        scenes[key] = {
            title: s.title, type: 'equirectangular', panorama: s.image, pitch: s.pitch, yaw: s.yaw,
            hotSpots: s.hotspots.map(h => h.type === 'scene' && h.target
                ? { pitch: h.pitch, yaw: h.yaw, type: 'scene', sceneId: h.target, text: h.title }
                : { pitch: h.pitch, yaw: h.yaw, cssClass: 'hs-custom' + (h.activity ? ' hs-act' : ''), createTooltipFunc: (div) => { div.title = h.title; }, clickHandlerFunc: () => openPanel(h) }),
        };
    });
    const viewer = pannellum.viewer('explorePano', { default: { firstScene: 'scene-{{ $start }}', sceneFadeDuration: 800, autoLoad: true, autoRotate: -2, compass: true }, scenes });
    const info = document.getElementById('sceneInfo');
    const onScene = () => {
        const id = viewer.getScene(), s = data[id];
        info.textContent = `${s.title}${s.description ? ' – ' + s.description : ''} · Camera: ${s.camera}`;
        document.querySelectorAll('.scene-pick').forEach(b => b.classList.toggle('active', b.dataset.scene === id));
        panel.classList.add('d-none');
        fetch(`{{ url('scenes') }}/${s.id}/view`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } }).catch(() => {});
    };
    viewer.on('load', onScene);
    document.querySelectorAll('.scene-pick').forEach(b => b.addEventListener('click', () => viewer.loadScene(b.dataset.scene)));
})();
</script>
@endpush
@endif
