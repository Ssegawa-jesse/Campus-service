@extends('layouts.app')
@section('title', 'Map & routes')
@php
    $c = config('murchison');
    $colors = ['Waterfall' => '#1d5c7a', 'River' => '#2b8fb8', 'Wildlife' => '#d9822b', 'Forest' => '#2e6b52', 'Gate' => '#7a4d9c', 'On the way' => '#a33a2c', 'Town' => '#5d6b64'];
    $places = collect($c['attractions'])->map(fn ($a, $id) => $a + ['id' => $id, 'color' => $colors[$a['category']] ?? '#333', 'on' => in_array($id, $highlight, true)])->values();
@endphp
@push('styles')<link href="{{ asset('vendor/leaflet/leaflet.css') }}" rel="stylesheet">@endpush

@section('content')
<section class="bg-forest py-5">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">Smart navigation</div>
        <h1 class="fw-bold mb-2">Routes and attraction locations</h1>
        <p class="mb-0 opacity-75" style="max-width:720px">See where every attraction, gate and stop is, how far it is from Paraa, and get directions from where you are.</p>
    </div>
</section>
<div class="container-xl py-4">
    @if ($highlight)<div class="alert alert-info py-2 small">Showing the {{ count($highlight) }} places in your itinerary (larger markers).</div>@endif
    <div class="row g-3">
        <div class="col-lg-4">
            <select class="form-select mb-2" id="cat"><option value="">Show all places</option>@foreach (array_keys($colors) as $cat)<option>{{ $cat }}</option>@endforeach</select>
            <div id="places" style="max-height:560px;overflow:auto">
                @foreach ($places as $p)
                    <div class="place-item {{ $p['on'] ? 'on' : '' }}" data-id="{{ $p['id'] }}" data-cat="{{ $p['category'] }}">
                        <div class="fw-semibold"><span style="color: {{ $p['color'] }}">●</span> {{ $p['name'] }} @if ($p['on'])<span class="badge bg-warning text-dark">In your plan</span>@endif</div>
                        <div class="small text-muted">{{ $p['category'] }} · <span class="dist" data-lat="{{ $p['lat'] }}" data-lng="{{ $p['lng'] }}"></span></div>
                        <div class="details small mt-1 d-none">{{ $p['description'] }} @if ($p['tip'])<div class="mt-1">💡 {{ $p['tip'] }}</div>@endif
                            <a class="d-block mt-1" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $p['lat'] }},{{ $p['lng'] }}">Get directions in Google Maps ↗</a></div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-lg-8">
            <div id="mf-map" class="mf-map shadow-sm"></div>
            <div class="d-flex flex-wrap gap-3 small mt-2">
                @foreach ($colors as $cat => $col)<span><span style="color: {{ $col }}">●</span> {{ $cat }}</span>@endforeach
                <span style="color:#d9822b">- - Route from Kampala</span>
            </div>
        </div>
    </div>
    <div class="row g-3 mt-2">
        @foreach ($c['origins'] as $o)
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body small">
                <h3 class="h6 fw-bold">From {{ $o['label'] }}</h3>
                About <strong>{{ $o['km_to_gate'] }} km</strong> to Kichumbanyobo Gate ({{ $o['hours_to_gate'] }} hours), then about {{ $c['gate_to_paraa_km'] }} km inside the park to Paraa.
            </div></div></div>
        @endforeach
    </div>
    <p class="small text-muted mt-3">Map positions and the route are approximate and for planning only. Use a navigation app and follow park signs and ranger instructions on the ground.</p>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
(function () {
    var places = @json($places);
    var highlight = @json($highlight);
    var paraa = places.find(function (p) { return p.id === 'paraa'; });
    var map = L.map('mf-map').setView([{{ $c['centre']['lat'] }}, {{ $c['centre']['lng'] }}], 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    // Approximate road route from Kampala to Paraa through Masindi and the southern gate (for orientation only).
    L.polyline([[0.3476, 32.5825], [0.849, 32.473], [1.31, 32.46], [1.47, 32.07], [1.6744, 31.715], [1.97, 31.77], [2.2786, 31.6857], [2.2843, 31.5655]],
        { color: '#d9822b', weight: 4, dashArray: '8 6' }).addTo(map).bindTooltip('Approximate route from Kampala via Masindi');
    L.circleMarker([0.3476, 32.5825], { radius: 7, color: '#fff', weight: 2, fillColor: '#1d2521', fillOpacity: 1 }).addTo(map).bindPopup('<strong>Kampala</strong>');
    var markers = {};
    function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
    places.forEach(function (p) {
        markers[p.id] = L.circleMarker([p.lat, p.lng], { radius: p.on ? 11 : 8, color: '#fff', weight: 2, fillColor: p.color, fillOpacity: .95 }).addTo(map)
            .bindPopup('<strong>' + esc(p.name) + '</strong><br><em>' + esc(p.category) + '</em><br>' + esc(p.description) +
                '<br><a target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=' + p.lat + ',' + p.lng + '">Get directions ↗</a>');
    });
    var focus = highlight.length ? places.filter(function (p) { return p.on; }) : places;
    map.fitBounds(L.latLngBounds(focus.map(function (p) { return [p.lat, p.lng]; })).pad(0.25));

    function km(a, b) {
        var R = 6371, r = function (d) { return d * Math.PI / 180; };
        var h = Math.pow(Math.sin(r(b.lat - a.lat) / 2), 2) + Math.cos(r(a.lat)) * Math.cos(r(b.lat)) * Math.pow(Math.sin(r(b.lng - a.lng) / 2), 2);
        return Math.round(2 * R * Math.asin(Math.sqrt(h)));
    }
    document.querySelectorAll('.dist').forEach(function (el) {
        el.textContent = 'about ' + km(paraa, { lat: +el.dataset.lat, lng: +el.dataset.lng }) + ' km from Paraa (straight line)';
    });
    document.querySelectorAll('.place-item').forEach(function (item) {
        item.addEventListener('click', function () {
            document.querySelectorAll('.place-item').forEach(function (o) { o.classList.remove('on'); o.querySelector('.details').classList.add('d-none'); });
            item.classList.add('on'); item.querySelector('.details').classList.remove('d-none');
            var p = places.find(function (x) { return x.id === item.dataset.id; });
            map.setView([p.lat, p.lng], 12); markers[p.id].openPopup();
        });
    });
    document.getElementById('cat').addEventListener('change', function () {
        var v = this.value;
        document.querySelectorAll('.place-item').forEach(function (item) { item.style.display = !v || item.dataset.cat === v ? '' : 'none'; });
    });
})();
</script>
@endpush
