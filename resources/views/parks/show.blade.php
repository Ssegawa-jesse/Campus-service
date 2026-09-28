@extends('layouts.app')
@section('title', $park->name)
@section('description', $park->tagline)

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')
<section class="park-hero" style="background-image:url('{{ $park->coverUrl() }}')">
    <div class="container-xl pb-4">
        <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="badge bg-gold">{{ \App\Models\Park::TYPES[$park->type] ?? 'Park' }}</span>
            <span class="badge bg-dark bg-opacity-50">{{ $park->region }} Uganda</span>
            @if ($park->is_unesco)<span class="badge bg-light text-dark"><i class="bi bi-award"></i> UNESCO World Heritage Site</span>@endif
        </div>
        <h1 class="display-5 fw-bold mb-1">{{ $park->name }}</h1>
        <p class="lead mb-3">{{ $park->tagline }}</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('tour.show', $park) }}" class="btn btn-gold btn-lg"><i class="bi bi-badge-vr me-1"></i> Start 360° tour ({{ $park->scenes->count() }} scenes)</a>
            @auth
                @if (auth()->user()->isTourist())
                    <a href="{{ route('tourist.bookings.create', ['park' => $park->slug]) }}" class="btn btn-light btn-lg"><i class="bi bi-calendar-plus me-1"></i> Book / enquire</a>
                    <form method="POST" action="{{ route('tourist.favourites.toggle', $park) }}">@csrf
                        <button class="btn btn-outline-light btn-lg"><i class="bi bi-heart{{ $isFavourite ? '-fill text-crane' : '' }}"></i> {{ $isFavourite ? 'Saved' : 'Save' }}</button>
                    </form>
                @endif
            @else
                <a href="{{ route('login') }}" class="btn btn-light btn-lg"><i class="bi bi-calendar-plus me-1"></i> Log in to book</a>
            @endauth
        </div>
    </div>
</section>

<div class="container-xl py-5">
    <div class="row g-5">
        <div class="col-lg-8">
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3"><div class="fact"><i class="bi bi-cash-coin"></i><div><div class="small text-muted">From</div><strong>${{ number_format($park->min_cost) }}</strong><div class="small text-muted">≈ {{ ugx(usd_to_ugx($park->min_cost)) }}</div></div></div></div>
                <div class="col-6 col-md-3"><div class="fact"><i class="bi bi-activity"></i><div><div class="small text-muted">Difficulty</div><strong>{{ $park->difficultyLabel() }}</strong></div></div></div>
                <div class="col-6 col-md-3"><div class="fact"><i class="bi bi-calendar-week"></i><div><div class="small text-muted">Stay</div><strong>{{ $park->min_days }}+ day{{ $park->min_days > 1 ? 's' : '' }}</strong></div></div></div>
                <div class="col-6 col-md-3"><div class="fact"><i class="bi bi-signpost-split"></i><div><div class="small text-muted">From Kampala</div><strong>{{ $park->distance_from_kampala_km }} km</strong></div></div></div>
            </div>

            <h3>About the park</h3>
            <p class="lead-sm">{!! nl2br(e($park->description)) !!}</p>

            <div class="d-flex flex-wrap gap-2 mb-4">
                @foreach ($park->interestLabels() as $label)<span class="chip">{{ $label }}</span>@endforeach
            </div>

            @if ($park->video_url)
                <h4 class="mt-4">Watch</h4>
                <div class="ratio ratio-16x9 ratio-video mb-4">
                    @if (youtube_embed($park->video_url))
                        <iframe src="{{ youtube_embed($park->video_url) }}" allowfullscreen loading="lazy" title="{{ $park->name }} video"></iframe>
                    @else
                        <video src="{{ media_url($park->video_url) }}" controls preload="metadata"></video>
                    @endif
                </div>
            @endif

            <h3 class="mt-4">Things to do</h3>
            <div class="row g-3 mb-4">
                @forelse ($park->activities as $activity)
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1">{{ $activity->name }}</h6>
                                    <span class="text-forest fw-semibold small">${{ number_format($activity->indicative_cost) }}</span>
                                </div>
                                <p class="small text-muted mb-2">{{ $activity->description }}</p>
                                <div class="small d-flex gap-3 text-muted">
                                    <span><i class="bi bi-clock"></i> {{ $activity->duration }}</span>
                                    <span><i class="bi bi-heart-pulse"></i> {{ $activity->fitnessLabel() }} fitness</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">Activities coming soon.</p>
                @endforelse
            </div>
            <p class="small text-muted"><i class="bi bi-info-circle"></i> Prices are indicative foreign non-resident rates in USD. East African residents and citizens pay lower rates – confirm current tariffs with UWA.</p>

            @if ($park->media->isNotEmpty())
                <h3 class="mt-4">Photos & videos</h3>
                <div class="row g-3 mb-4">
                    @foreach ($park->media as $m)
                        <div class="col-6 col-md-4">
                            @if ($m->type === 'image')
                                <img src="{{ $m->url() }}" data-lightbox="{{ $m->url() }}" alt="{{ $m->caption }}" class="gallery-thumb" loading="lazy">
                            @elseif ($m->type === 'youtube')
                                <div class="ratio ratio-16x9 ratio-video"><iframe src="{{ $m->embedUrl() }}" allowfullscreen loading="lazy" title="{{ $m->caption }}"></iframe></div>
                            @else
                                <div class="ratio ratio-16x9 ratio-video"><video src="{{ $m->url() }}" controls preload="metadata"></video></div>
                            @endif
                            @if ($m->caption)<div class="small text-muted mt-1">{{ $m->caption }}</div>@endif
                        </div>
                    @endforeach
                </div>
            @endif

            <h3 class="mt-4 d-flex align-items-center gap-2">Reviews
                @if ($park->reviews->isNotEmpty())<small class="fs-6">@include('partials.stars', ['rating' => round($park->reviews->avg('rating'), 1)]) {{ number_format($park->reviews->avg('rating'), 1) }} ({{ $park->reviews->count() }})</small>@endif
            </h3>
            @auth
                @if (auth()->user()->isTourist())
                    <form method="POST" action="{{ route('tourist.reviews.store') }}" class="card border-0 shadow-sm mb-3">
                        @csrf
                        <input type="hidden" name="park_id" value="{{ $park->id }}">
                        <div class="card-body row g-2">
                            <div class="col-md-3">
                                <select name="rating" class="form-select" required>
                                    @for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ str_repeat('★', $i) }}</option>@endfor
                                </select>
                            </div>
                            <div class="col-md-7"><input name="comment" class="form-control" placeholder="Share your experience (min 10 characters)" required minlength="10"></div>
                            <div class="col-md-2 d-grid"><button class="btn btn-forest">Post</button></div>
                        </div>
                    </form>
                @endif
            @endauth
            @forelse ($park->reviews->take(8) as $review)
                <div class="border-bottom py-3">
                    <div class="d-flex justify-content-between"><strong>{{ $review->user->name }}</strong><small class="text-muted">{{ $review->created_at->diffForHumans() }}</small></div>
                    @include('partials.stars', ['rating' => $review->rating])
                    <p class="mb-0 mt-1">{{ $review->comment }}</p>
                </div>
            @empty
                <p class="text-muted">No reviews yet. Be the first!</p>
            @endforelse
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5><i class="bi bi-sun text-gold"></i> Best time to visit</h5>
                    <p class="mb-2">{{ $park->best_time }}</p>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach (month_names() as $n => $m)
                            <span class="badge {{ in_array($n, $park->best_months ?? []) ? 'bg-forest' : 'bg-light text-muted' }}">{{ substr($m, 0, 3) }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5><i class="bi bi-map text-gold"></i> Location</h5>
                    <div id="miniMap" class="mini-map mb-2"></div>
                    <p class="small mb-1">{{ $park->how_to_get_there }}</p>
                    <a class="small" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $park->latitude }},{{ $park->longitude }}"><i class="bi bi-sign-turn-right"></i> Get directions (Google Maps)</a>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body small">
                    <h5><i class="bi bi-info-square text-gold"></i> Quick facts</h5>
                    <dl class="row mb-0">
                        @if ($park->established)<dt class="col-6">Gazetted</dt><dd class="col-6">{{ $park->established }}</dd>@endif
                        @if ($park->area_km2)<dt class="col-6">Area</dt><dd class="col-6">{{ number_format($park->area_km2) }} km²</dd>@endif
                        <dt class="col-6">Coordinates</dt><dd class="col-6">{{ number_format($park->latitude, 3) }}, {{ number_format($park->longitude, 3) }}</dd>
                        <dt class="col-6">Page views</dt><dd class="col-6">{{ number_format($park->views_count) }}</dd>
                    </dl>
                </div>
            </div>

            @if ($park->packages->isNotEmpty())
                <h5>Packages for this park</h5>
                @foreach ($park->packages->take(4) as $package)
                    <a href="{{ route('packages.show', $package) }}" class="card border-0 shadow-sm mb-2 text-reset">
                        <div class="card-body py-2">
                            <div class="fw-semibold">{{ $package->title }}</div>
                            <div class="small text-muted">{{ $package->operator->company_name }} · {{ $package->days }} days · <span class="text-forest fw-semibold">{{ ugx($package->price_ugx) }}</span> pp</div>
                        </div>
                    </a>
                @endforeach
            @endif

            <h5 class="mt-4">Nearby parks</h5>
            @foreach ($nearby as $n)
                <a href="{{ route('parks.show', $n) }}" class="d-flex gap-2 align-items-center mb-2 text-reset">
                    <img src="{{ $n->coverUrl() }}" width="64" height="44" class="rounded object-fit-cover" alt="">
                    <span class="small fw-semibold">{{ $n->name }}</span>
                </a>
            @endforeach
            <a href="{{ route('parks.compare', ['parks' => [$park->id, $nearby->first()?->id]]) }}" class="btn btn-sm btn-outline-forest mt-2 w-100"><i class="bi bi-layout-split"></i> Compare with nearby</a>
        </div>
    </div>
</div>

<div class="modal fade" id="lightbox" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content bg-dark border-0">
    <img src="" class="img-fluid rounded-top" alt=""><div class="caption text-white-50 small p-2"></div>
</div></div></div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
const mm = L.map('miniMap', { scrollWheelZoom: false }).setView([{{ $park->latitude }}, {{ $park->longitude }}], 8);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mm);
L.marker([{{ $park->latitude }}, {{ $park->longitude }}]).addTo(mm).bindPopup(@json($park->name)).openPopup();
</script>
@endpush
