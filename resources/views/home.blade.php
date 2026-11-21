@extends('layouts.app')
@section('title', 'Smart Trip Planning for Murchison Falls')

@section('content')
<section class="hero position-relative text-white" style="background: linear-gradient(rgba(10,30,20,.55), rgba(10,30,20,.75)), url('{{ $park?->coverUrl() }}') center/cover;">
    <div class="container-xl py-5">
        <div class="row align-items-center py-lg-5">
            <div class="col-lg-7">
                <div class="section-kicker text-gold">Murchison Falls National Park · Uganda</div>
                <h1 class="display-5 fw-bold">Plan your Murchison Falls trip in minutes — with AI.</h1>
                <p class="lead text-white">A personalised day-by-day itinerary, UWA-priced budget, park map with travel times, a travel assistant, live alerts, and 360° previews of every activity.</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('itinerary.index') }}" class="btn btn-gold btn-lg"><i class="bi bi-calendar2-week me-1"></i> Build my Smart Itinerary</a>
                    <a href="{{ route('assistant') }}" class="btn btn-outline-light btn-lg"><i class="bi bi-robot me-1"></i> Ask the assistant</a>
                </div>
            </div>
            <div class="col-lg-5 mt-4 mt-lg-0">
                <div class="card border-0 shadow text-dark">
                    <div class="card-body">
                        <h6 class="mb-2"><i class="bi bi-broadcast text-danger"></i> Live travel updates</h6>
                        @forelse ($updates as $u)
                            <div class="alert alert-{{ $u->color() }} py-2 px-3 small mb-2"><i class="bi bi-{{ $u->icon() }} me-1"></i><strong>{{ $u->title }}</strong><div>{{ Str::limit($u->body, 110) }}</div></div>
                        @empty
                            <p class="small text-muted mb-0">No alerts right now.</p>
                        @endforelse
                        <a href="{{ route('updates') }}" class="small">All updates & weather →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container-xl py-5">
    <div class="text-center mb-4">
        <div class="section-kicker">System architecture</div>
        <h2 class="fw-bold">Smart Trip Planning – six modules</h2>
        <p class="text-muted">Smart Itinerary is the core. The other five modules feed it.</p>
    </div>
    <div class="row g-3">
        @foreach ([
            ['itinerary.index', 'calendar2-week', 'Smart Itinerary', 'Primary module: a personalised multi-day schedule of boat cruises, game drives, hikes, chimps, shoebills, fishing and night drives.', 'bg-warning-subtle text-warning-emphasis', true],
            ['budget', 'calculator', 'Travel Budgeting', 'UWA tiered pricing for Foreign Non-Residents and Residents (USD) and East African Citizens (UGX).', 'bg-success-subtle text-success', false],
            ['navigate', 'map', 'Smart Navigation', 'Working map of the gates, Paraa Jetty, Baker\'s Trail, Pakuba Airfield and attractions, with distances and times.', 'bg-primary-subtle text-primary', false],
            ['assistant', 'robot', 'AI Travel Assistant', 'Ask about weather, seasons, packing, UWA rules, or say "plan 3 days for $1,000".', 'bg-info-subtle text-info-emphasis', false],
            ['updates', 'broadcast', 'Travel Updates', 'Paraa ferry schedules, road and gate status, weather advisories and UWA conservation notices.', 'bg-danger-subtle text-danger', false],
            ['explore', 'badge-vr', 'Virtual Exploration', '360° previews from an HP5 camera, with hotspots that add activities to your itinerary.', 'bg-secondary-subtle text-secondary-emphasis', false],
        ] as $i => [$r, $icon, $title, $text, $tone, $primary])
            <div class="col-md-6 col-lg-4">
                <a href="{{ route($r) }}" class="card module-card shadow-sm text-decoration-none text-reset {{ $primary ? 'module-primary' : '' }}">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="module-icon {{ $tone }}"><i class="bi bi-{{ $icon }}"></i></div>
                            <div><div class="small text-muted">Module {{ $i + 1 }}{{ $primary ? ' · primary target' : '' }}</div><h5 class="mb-0">{{ $title }}</h5></div>
                        </div>
                        <p class="small text-muted mb-0">{{ $text }}</p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
    <div class="row g-3 text-center mt-4">
        @foreach (['activities' => 'Park activities', 'places' => 'Mapped places & gates', 'scenes' => 'HP5 360° scenes', 'itineraries' => 'Itineraries generated'] as $k => $l)
            <div class="col-6 col-md-3"><div class="p-3 bg-white rounded-4 shadow-sm"><div class="fs-2 fw-bold text-forest">{{ number_format($stats[$k]) }}</div><div class="small text-muted">{{ $l }}</div></div></div>
        @endforeach
    </div>
</section>

<section class="bg-white py-5">
    <div class="container-xl">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div><div class="section-kicker">Murchison Falls activities</div><h2 class="fw-bold mb-0">What you can do</h2></div>
            <a href="{{ route('budget') }}" class="btn btn-outline-forest">UWA prices <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-3">
            @foreach ($activities as $a)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-forest-light text-forest align-self-start mb-2">{{ \App\Models\Activity::CATEGORIES[$a->category] ?? $a->category }} · {{ $a->place?->name }}</span>
                            <h6>{{ $a->name }}</h6>
                            <p class="small text-muted flex-grow-1">{{ $a->description }}</p>
                            <div class="small mb-2"><strong>{{ $a->priceLabel('fnr') }}</strong> FNR · {{ $a->priceLabel('fr') }} FR · {{ $a->priceLabel('eac') }} EAC</div>
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('itinerary.add', $a) }}">@csrf<button class="btn btn-sm btn-gold"><i class="bi bi-calendar-plus"></i> Add to itinerary</button></form>
                                <a href="{{ route('explore', ['activity' => $a->slug]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-badge-vr"></i> 360°</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@if ($packages->isNotEmpty())
<section class="container-xl py-5">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div><div class="section-kicker">From verified tour operators</div><h2 class="fw-bold mb-0">Murchison Falls safari packages</h2></div>
        <a href="{{ route('packages.index') }}" class="btn btn-outline-forest">All packages</a>
    </div>
    <div class="row g-3">
        @foreach ($packages as $package)<div class="col-md-4">@include('packages._card')</div>@endforeach
    </div>
</section>
@endif
@endsection
