@extends('layouts.app')
@section('title', 'Interactive Map of Uganda\'s National Parks')
@section('no_footer', true)
@section('no_flash', true)

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')
<div class="position-relative">
    <div class="map-panel card shadow border-0">
        <div class="card-body">
            <h5 class="mb-1"><i class="bi bi-geo-alt-fill text-crane"></i> Uganda's National Parks</h5>
            <p class="small text-muted">Click a park to fly there. Switch to satellite view in the top-right corner.</p>
            <input type="search" class="form-control form-control-sm mb-2" id="parkFilter" placeholder="Filter parks…">
            <div class="list-group list-group-flush small" id="parkList">
                @foreach ($parks as $park)
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-id="{{ $park->id }}" data-name="{{ strtolower($park->name.' '.$park->region) }}">
                        <span><strong>{{ Str::before($park->name, ' National Park') }}</strong><br><span class="text-muted">{{ $park->region }} · {{ $park->distance_from_kampala_km }} km from Kampala</span></span>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                @endforeach
            </div>
            <div class="form-check form-switch mt-3 small">
                <input class="form-check-input" type="checkbox" id="locateMe">
                <label class="form-check-label" for="locateMe">Show my location & distances</label>
            </div>
        </div>
    </div>
    <div id="parksMap"></div>
</div>
@endsection

@push('scripts')
@php $mapData = $parks->map(fn ($p) => [
    'id' => $p->id, 'name' => $p->name, 'lat' => $p->latitude, 'lng' => $p->longitude, 'region' => $p->region,
    'tagline' => $p->tagline, 'img' => $p->coverUrl(), 'url' => route('parks.show', $p), 'tour' => route('tour.show', $p),
    'scenes' => $p->scenes_count, 'cost' => (float) $p->min_cost, 'unesco' => $p->is_unesco,
])->values(); @endphp
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
const parks = {!! Js::from($mapData) !!};

const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' });
const sat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, attribution: 'Tiles &copy; Esri' });
const topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { maxZoom: 17, attribution: '&copy; OpenTopoMap' });

const map = L.map('parksMap', { layers: [osm] }).setView([1.37, 32.29], 7);
L.control.layers({ 'Street map': osm, 'Satellite': sat, 'Terrain': topo }).addTo(map);
L.control.scale().addTo(map);

const icon = (unesco) => L.divIcon({
    className: '', iconSize: [34, 34], iconAnchor: [17, 34], popupAnchor: [0, -30],
    html: `<svg width="34" height="34" viewBox="0 0 24 24"><path fill="${unesco ? '#e0a526' : '#1f4d3a'}" stroke="#fff" stroke-width="1.2" d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7z"/><circle cx="12" cy="9" r="2.6" fill="#fff"/></svg>`
});

const markers = {};
const kampala = L.latLng(0.3476, 32.5825);
L.circleMarker(kampala, { radius: 7, color: '#c8102e', fillOpacity: .9 }).addTo(map).bindTooltip('Kampala', { permanent: true, direction: 'right' });

parks.forEach(p => {
    const m = L.marker([p.lat, p.lng], { icon: icon(p.unesco), title: p.name }).addTo(map);
    m.bindPopup(`<div class="map-popup" style="width:220px">
        <img src="${p.img}" alt="">
        <h6 class="mt-2 mb-1">${p.name}</h6>
        <div class="small text-muted mb-2">${p.tagline ?? ''}</div>
        <div class="small mb-2"><i class="bi bi-badge-vr"></i> ${p.scenes} scenes · from $${p.cost.toLocaleString()}</div>
        <div class="d-flex gap-1"><a class="btn btn-sm btn-forest text-white flex-fill" href="${p.tour}">360° tour</a><a class="btn btn-sm btn-outline-forest flex-fill" href="${p.url}">Details</a></div>
    </div>`);
    markers[p.id] = m;
});

document.querySelectorAll('#parkList [data-id]').forEach(btn => btn.addEventListener('click', () => {
    const m = markers[btn.dataset.id];
    map.flyTo(m.getLatLng(), 10, { duration: 1.2 });
    setTimeout(() => m.openPopup(), 1200);
}));

document.getElementById('parkFilter').addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('#parkList [data-id]').forEach(b => b.classList.toggle('d-none', !b.dataset.name.includes(q)));
});

document.getElementById('locateMe').addEventListener('change', e => {
    if (!e.target.checked) return;
    map.locate({ setView: false });
});
map.on('locationfound', ev => {
    L.marker(ev.latlng).addTo(map).bindPopup('You are here').openPopup();
    parks.forEach(p => {
        const km = Math.round(ev.latlng.distanceTo([p.lat, p.lng]) / 1000);
        markers[p.id].bindTooltip(`${km} km away (straight line)`);
    });
});
map.on('locationerror', () => alert('Could not get your location.'));
</script>
@endpush
