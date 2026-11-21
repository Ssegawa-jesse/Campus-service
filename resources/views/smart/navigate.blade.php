@extends('layouts.app')
@section('title', 'Smart Navigation – Park Map')

@push('styles')<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">@endpush

@section('content')
<section class="bg-forest py-4">
    <div class="container-xl">
        <div class="section-kicker text-gold">Module 3</div>
        <h1 class="fw-bold mb-1">Smart Navigation</h1>
        <p class="text-white-50 mb-0">Working map of Murchison Falls: the gates (Kichumbanyobo, Bugungu, Tangi, Chobe, Wankwar), Paraa Jetty, Baker's Trail, Pakuba Airfield and the attractions, with route distances and travel times.</p>
    </div>
</section>
<div class="container-xl py-4">
    <div class="row g-4">
        <div class="col-lg-8"><div id="navMap" class="shadow-sm"></div></div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3"><div class="card-body">
                <h5><i class="bi bi-signpost-split text-forest"></i> Route planner</h5>
                <div class="mb-2"><label class="form-label small">From</label><select id="from" class="form-select form-select-sm">@foreach ($places as $p)<option value="{{ $p->id }}" @selected($p->slug === 'kichumbanyobo-gate')>{{ $p->name }}</option>@endforeach</select></div>
                <div class="mb-2"><label class="form-label small">To</label><select id="to" class="form-select form-select-sm">@foreach ($places as $p)<option value="{{ $p->id }}" @selected($p->slug === 'paraa-jetty')>{{ $p->name }}</option>@endforeach</select></div>
                <button id="routeBtn" class="btn btn-gold btn-sm w-100"><i class="bi bi-arrow-right-circle"></i> Distance & travel time</button>
                <div id="routeOut" class="small mt-3" aria-live="polite"></div>
                <button id="locBtn" class="btn btn-outline-secondary btn-sm w-100 mt-2"><i class="bi bi-crosshair"></i> Show my location</button>
                <p class="small text-muted mt-2 mb-0">Estimates use straight-line distance × 1.35 for park tracks at ~30 km/h (UWA limit 40 km/h), plus ~30 min when the route crosses the Nile on the Paraa ferry.</p>
            </div></div>
            <div class="card border-0 shadow-sm"><div class="card-body">
                <h6>Layers</h6>
                @foreach (\App\Models\Place::TYPES as $k => [$label, $icon, $color])
                    @if ($places->where('type', $k)->isNotEmpty())
                        <div class="form-check small"><input class="form-check-input layer-toggle" type="checkbox" value="{{ $k }}" id="l-{{ $k }}" checked>
                            <label class="form-check-label" for="l-{{ $k }}"><i class="bi bi-{{ $icon }}" style="color: {{ $color }}"></i> {{ $label }} ({{ $places->where('type', $k)->count() }})</label></div>
                    @endif
                @endforeach
            </div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
(() => {
    const places = {!! Js::from($markers) !!};
    const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' });
    const sat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Imagery © Esri' });
    const map = L.map('navMap', { layers: [street] }).setView([2.2, 31.7], 9);
    L.control.layers({ 'Street': street, 'Satellite': sat }).addTo(map);
    const layers = {};
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    places.forEach(p => {
        const icon = L.divIcon({ className: '', html: `<div style="background:${p.color};color:#fff;width:30px;height:30px;border-radius:50%;display:grid;place-items:center;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"><i class="bi bi-${p.icon}"></i></div>`, iconSize: [30, 30], iconAnchor: [15, 15] });
        const acts = p.activities.map(a => `<li>${esc(a.name)} <form method="POST" action="{{ url('itinerary/add') }}/${a.slug}" style="display:inline"><input type="hidden" name="_token" value="${csrf}"><button class="btn btn-link btn-sm p-0">+ itinerary</button></form></li>`).join('');
        const m = L.marker([p.lat, p.lng], { icon }).bindPopup(`<strong>${esc(p.name)}</strong><br><small class="text-muted">${esc(p.typeLabel)}${p.hours ? ' · ' + esc(p.hours) : ''}</small><p class="mb-1 small">${esc(p.description)}</p>${p.tip ? `<p class="small mb-1"><em>Tip: ${esc(p.tip)}</em></p>` : ''}${acts ? `<ul class="small ps-3 mb-0">${acts}</ul>` : ''}`);
        (layers[p.type] ??= L.layerGroup().addTo(map)).addLayer(m);
    });
    document.querySelectorAll('.layer-toggle').forEach(cb => cb.addEventListener('change', () => cb.checked ? layers[cb.value].addTo(map) : map.removeLayer(layers[cb.value])));

    let line;
    document.getElementById('routeBtn').addEventListener('click', async () => {
        const from = document.getElementById('from').value, to = document.getElementById('to').value, out = document.getElementById('routeOut');
        if (from === to) { out.textContent = 'Choose two different places.'; return; }
        const r = await fetch(`{{ route('navigate.route') }}?from=${from}&to=${to}`, { headers: { Accept: 'application/json' } });
        const d = await r.json();
        if (!r.ok) { out.textContent = 'Could not calculate this route.'; return; }
        out.innerHTML = `<div class="p-2 bg-light rounded"><strong>${esc(d.from)} → ${esc(d.to)}</strong><br>Straight line: ${d.straight_km} km<br>By road ≈ <strong>${d.road_km} km</strong><br>Travel time ≈ <strong>${Math.floor(d.minutes / 60)} h ${d.minutes % 60} min</strong>${d.ferry ? '<br><i class="bi bi-water"></i> Includes the Paraa ferry crossing' : ''}</div>`;
        const a = places.find(p => p.id == from), b = places.find(p => p.id == to);
        if (line) map.removeLayer(line);
        line = L.polyline([[a.lat, a.lng], [b.lat, b.lng]], { color: '#d4a017', weight: 4, dashArray: '8 6' }).addTo(map);
        map.fitBounds(line.getBounds().pad(0.4));
    });
    document.getElementById('locBtn').addEventListener('click', () => map.locate({ setView: true, maxZoom: 11 }));
    map.on('locationfound', e => L.circleMarker(e.latlng, { radius: 8, color: '#2563eb' }).addTo(map).bindPopup('You are here').openPopup());
    map.on('locationerror', () => alert('Location is not available on this device.'));
})();
</script>
@endpush
