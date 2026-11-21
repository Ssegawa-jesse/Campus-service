@extends('layouts.app')
@section('title', 'Travel Updates')

@section('content')
<section class="bg-forest py-4">
    <div class="container-xl">
        <div class="section-kicker text-gold">Module 5</div>
        <h1 class="fw-bold mb-1">Travel Updates</h1>
        <p class="text-white-50 mb-0">Live operational alerts for Murchison Falls: Paraa ferry schedules, road and gate status, seasonal weather advisories and UWA conservation notices. Updated by the park research team.</p>
    </div>
</section>
<div class="container-xl py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            @forelse ($updates as $cat => $items)
                <h5 class="mt-2"><i class="bi bi-{{ \App\Models\TravelUpdate::CATEGORIES[$cat][1] ?? 'info-circle' }} text-forest"></i> {{ \App\Models\TravelUpdate::CATEGORIES[$cat][0] ?? ucfirst($cat) }}</h5>
                @foreach ($items as $u)
                    <div class="card border-0 shadow-sm mb-3 border-start border-4 border-{{ $u->color() }}"><div class="card-body">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <h6 class="mb-1">@if ($u->is_pinned)<i class="bi bi-pin-angle-fill text-gold"></i> @endif{{ $u->title }}</h6>
                            <span class="badge bg-{{ $u->color() }} text-capitalize">{{ $u->severity }}</span>
                        </div>
                        <p class="mb-1">{{ $u->body }}</p>
                        <div class="small text-muted">@if ($u->location)<i class="bi bi-geo-alt"></i> {{ $u->location }} · @endif Updated {{ $u->updated_at->diffForHumans() }}@if ($u->valid_until) · valid until {{ $u->valid_until->format('j M Y') }}@endif</div>
                    </div></div>
                @endforeach
            @empty
                <div class="alert alert-success">No active alerts – all gates, roads and the Paraa ferry are operating normally.</div>
            @endforelse
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <h5><i class="bi bi-cloud-sun text-forest"></i> 7-day forecast – {{ $weather['label'] }}</h5>
                <div id="wx" class="small text-muted">Loading forecast…</div>
                <p class="small text-muted mt-2 mb-0">Source: Open-Meteo (free, no key). Seasons: dry Dec–Feb & Jun–Aug; wet Mar–May & Sep–Nov.</p>
            </div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(async () => {
    const el = document.getElementById('wx');
    const codes = c => c === 0 ? ['☀️', 'Clear'] : c <= 2 ? ['🌤️', 'Partly cloudy'] : c === 3 ? ['☁️', 'Cloudy'] : c < 50 ? ['🌫️', 'Fog'] : c < 60 ? ['🌦️', 'Drizzle'] : c < 90 ? ['🌧️', 'Rain'] : ['⛈️', 'Thunderstorms'];
    try {
        const r = await fetch('https://api.open-meteo.com/v1/forecast?latitude={{ $weather['lat'] }}&longitude={{ $weather['lng'] }}&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=Africa%2FKampala&forecast_days=7');
        const d = (await r.json()).daily;
        el.innerHTML = '<table class="table table-sm mb-0">' + d.time.map((t, i) => { const [ic, lb] = codes(d.weather_code[i]); return `<tr><td>${new Date(t).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' })}</td><td title="${lb}">${ic}</td><td>${Math.round(d.temperature_2m_max[i])}° / ${Math.round(d.temperature_2m_min[i])}°</td><td>💧${d.precipitation_probability_max[i] ?? 0}%</td></tr>`; }).join('') + '</table>';
    } catch (e) { el.textContent = 'Forecast unavailable offline.'; }
})();
</script>
@endpush
