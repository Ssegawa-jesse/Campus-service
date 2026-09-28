@extends('layouts.app')
@section('title', 'Explore Uganda\'s National Parks in 360°')
@section('no_flash', true)

@section('content')
@php $heroImg = setting('hero_image') ? media_url(setting('hero_image')) : ($parks->firstWhere('slug', 'murchison-falls-national-park')?->coverUrl() ?? asset('images/placeholder-park.svg')); @endphp
<section class="hero" style="background-image:url('{{ $heroImg }}')">
    <div class="container-xl py-5">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="hero-badge mb-3"><i class="bi bi-badge-vr"></i> Virtual safari · {{ $stats['parks'] }} parks · {{ $stats['scenes'] }} 360° scenes</span>
                <h1 class="mb-3">See the Pearl of Africa <span class="text-gold">before you go.</span></h1>
                <p class="lead mb-4">{{ setting('site_tagline', 'Take immersive 360° tours of Uganda\'s national parks, get a trip plan matched to your interests, fitness and budget, then book trusted operators and pay with Mobile Money.') }}</p>
                <form action="{{ route('parks.index') }}" class="hero-search d-flex gap-2 mb-4">
                    <i class="bi bi-search text-muted align-self-center ms-2"></i>
                    <input name="q" class="form-control" placeholder="Search gorillas, waterfalls, Kidepo…">
                    <button class="btn btn-forest px-4">Search</button>
                </form>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('planner.index') }}" class="btn btn-gold btn-lg"><i class="bi bi-magic me-1"></i> Plan my trip</a>
                    <a href="{{ route('map') }}" class="btn btn-outline-light btn-lg"><i class="bi bi-map me-1"></i> Explore the map</a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container-xl stat-strip">
    <div class="row g-3">
        @foreach ([['parks', 'National parks', 'tree'], ['scenes', '360° scenes', 'badge-vr'], ['operators', 'Verified operators', 'patch-check'], ['trips', 'Trips booked', 'suitcase2']] as [$k, $label, $icon])
            <div class="col-6 col-md-3"><div class="stat-card"><i class="bi bi-{{ $icon }} text-gold fs-4"></i><div class="num">{{ number_format($stats[$k]) }}</div><div class="small text-muted">{{ $label }}</div></div></div>
        @endforeach
    </div>
</div>

<div class="container-xl">@include('partials.flash')</div>

<section class="container-xl py-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div><div class="section-kicker">Most explored</div><h2 class="section-title mb-0">Popular parks right now</h2></div>
        <a href="{{ route('parks.index') }}" class="btn btn-outline-forest">All {{ $parks->count() }} parks <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4">
        @foreach ($featured as $park)
            <div class="col-sm-6 col-lg-4">@include('partials.park-card')</div>
        @endforeach
    </div>
</section>

<section class="bg-white py-5">
    <div class="container-xl">
        <div class="text-center mb-5"><div class="section-kicker">How it works</div><h2 class="section-title">From dreaming to booking in four steps</h2></div>
        <div class="row g-4 text-center">
            @foreach ([
                ['badge-vr', 'Take a 360° tour', 'Look around gorilla trails, waterfalls and savannah from your phone or laptop – even with a VR headset.'],
                ['magic', 'Get matched', 'Answer 5 quick questions. Our smart planner ranks every park for your interests, fitness, budget and travel month.'],
                ['chat-square-text', 'Enquire & get a quote', 'Send one enquiry to a verified tour operator and receive a quote by SMS and email.'],
                ['phone', 'Pay with Mobile Money', 'Pay securely with MTN MoMo, Airtel Money or card and get instant SMS confirmation.'],
            ] as $i => [$icon, $title, $text])
                <div class="col-md-3">
                    <div class="d-inline-grid bg-forest-light rounded-4 p-3 mb-3" style="place-items:center"><i class="bi bi-{{ $icon }} fs-2 text-forest"></i></div>
                    <h5>{{ $i + 1 }}. {{ $title }}</h5>
                    <p class="text-muted small">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="container-xl py-5">
    <div class="row g-4 align-items-center">
        <div class="col-lg-5">
            <div class="section-kicker">Beyond the famous few</div>
            <h2 class="section-title">Hidden gems waiting to be explored</h2>
            <p class="text-muted">Most visitors only hear about gorilla trekking. These parks offer unique experiences with far fewer crowds – take a virtual look and see what you're missing.</p>
            <a href="{{ route('parks.compare') }}" class="btn btn-forest"><i class="bi bi-layout-split me-1"></i> Compare parks side by side</a>
        </div>
        <div class="col-lg-7">
            <div class="row g-3">
                @foreach ($hiddenGems as $park)
                    <div class="col-md-4">
                        <a href="{{ route('tour.show', $park) }}" class="card border-0 shadow-sm text-white overflow-hidden h-100" style="background:url('{{ $park->coverUrl() }}') center/cover;min-height:230px">
                            <div class="card-body d-flex flex-column justify-content-end" style="background:linear-gradient(0deg,rgba(0,0,0,.75),transparent)">
                                <span class="small text-gold"><i class="bi bi-badge-vr"></i> 360° tour</span>
                                <h6 class="mb-0">{{ $park->name }}</h6>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

@if ($packages->isNotEmpty())
<section class="bg-white py-5">
    <div class="container-xl">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div><div class="section-kicker">Book with confidence</div><h2 class="section-title mb-0">Safari packages from verified operators</h2></div>
            <a href="{{ route('packages.index') }}" class="btn btn-outline-forest">All packages</a>
        </div>
        <div class="row g-4">
            @foreach ($packages as $package)
                <div class="col-md-6 col-lg-4">@include('packages._card')</div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if ($reviews->isNotEmpty())
<section class="container-xl py-5">
    <div class="text-center mb-4"><div class="section-kicker">Travellers say</div><h2 class="section-title">Real experiences</h2></div>
    <div class="row g-4">
        @foreach ($reviews as $review)
            <div class="col-md-4">
                <div class="review-card">
                    @include('partials.stars', ['rating' => $review->rating])
                    <p class="mt-2 mb-3">"{{ $review->comment }}"</p>
                    <div class="small"><strong>{{ $review->user->name }}</strong> · <a href="{{ route('parks.show', $review->park) }}">{{ $review->park->name }}</a></div>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif

<section class="container-xl pb-4">
    <div class="cta-band p-4 p-md-5 d-md-flex justify-content-between align-items-center gap-4">
        <div>
            <h3 class="mb-2">Are you a tour operator or lodge?</h3>
            <p class="mb-md-0 text-white-50">Receive enquiries from tourists who have already explored the parks virtually. Get paid online via Mobile Money.</p>
        </div>
        <a href="{{ route('register', ['as' => 'operator']) }}" class="btn btn-gold btn-lg flex-shrink-0">Join as an operator</a>
    </div>
</section>
@endsection
