@extends('layouts.app')
@section('title', 'Plan your trip to Murchison Falls')
@section('no_flash', true)
@php
    $features = [
        ['plan.index', '🗓️', 'Smart itinerary', 'Personalised day-by-day travel schedules built from your interests, dates, fitness and budget.', true],
        ['budget', '💰', 'Travel budgeting', 'Estimated trip expenditure: park fees, activities, accommodation, transport and meals.', false],
        ['map', '🧭', 'Smart navigation', 'Routes from Kampala and the locations of every attraction on an interactive map.', false],
        ['explore', '👁️', 'Virtual exploration', '360° scenes and interactive hotspots for the activities in the park.', false],
        ['assistant', '💬', 'AI travel assistant', 'Answers questions about the park and recommends activities.', false],
        ['updates', '⛅', 'Travel updates', 'Live weather at Paraa, the best months to visit and important travel information.', false],
    ];
@endphp

@section('content')
<section class="hero position-relative text-white" style="background: linear-gradient(180deg, rgba(10,30,22,.35), rgba(10,30,22,.8)), url('{{ $park?->coverUrl() ?? asset('images/placeholder-park.svg') }}') center/cover;">
    <div class="container-xl py-5">
        <div class="py-lg-5" style="max-width: 760px">
            <div class="section-kicker text-gold">Uganda Wildlife Authority · Murchison Falls National Park</div>
            <h1 class="display-5 fw-bold">Plan your perfect trip to Murchison Falls</h1>
            <p class="lead opacity-90">Tell us what you love, when you are coming and how much you want to spend. We build your day-by-day itinerary, estimate the cost, map the route and let you explore the park before you travel.</p>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="{{ route('plan.index') }}" class="btn btn-gold btn-lg fw-semibold"><i class="bi bi-magic"></i> Plan my trip</a>
                <a href="{{ route('explore') }}" class="btn btn-outline-light btn-lg">Explore in 360°</a>
            </div>
        </div>
    </div>
</section>

<div class="container-xl">@include('partials.flash')</div>

<section class="container-xl py-5">
    <h2 class="fw-bold">Everything you need to plan your visit</h2>
    <p class="text-muted">One platform for Murchison Falls National Park, Uganda's largest national park.</p>
    <div class="row g-3 mt-1">
        @foreach ($features as [$route, $icon, $title, $text, $main])
            <div class="col-md-6">
                <a href="{{ route($route) }}" class="card feature-card {{ $main ? 'main' : '' }} h-100 p-3 shadow-sm">
                    <span class="icon" aria-hidden="true">{{ $icon }}</span>
                    <span><span class="h5 fw-bold d-block mb-1">{{ $title }} @if ($main)<span class="badge bg-warning text-dark align-middle">Start here</span>@endif</span>
                        <span class="text-muted small">{{ $text }}</span></span>
                </a>
            </div>
        @endforeach
    </div>
</section>

<section class="bg-white py-5">
    <div class="container-xl">
        <h2 class="fw-bold mb-3">How it works</h2>
        <div class="row g-3">
            @foreach ([['Explore virtually', 'Look around the falls, the Nile and the savannah in 360°.'], ['Choose your interests', 'Big game, boat trips, chimpanzees, the shoebill, hiking…'], ['Get your itinerary', 'A timed schedule for each day, with reasons for every choice.'], ['Book and pay', 'Send your itinerary to an approved tour operator and pay by Mobile Money or card.']] as $i => [$t, $d])
                <div class="col-md-6 col-lg-3"><div class="card border-0 bg-light h-100"><div class="card-body">
                    <span class="badge rounded-circle bg-success mb-2" style="width:30px;height:30px;line-height:22px">{{ $i + 1 }}</span>
                    <h3 class="h6 fw-bold">{{ $t }}</h3><p class="small text-muted mb-0">{{ $d }}</p>
                </div></div></div>
            @endforeach
        </div>
    </div>
</section>

<section class="container-xl py-5">
    <div class="row g-4 align-items-center">
        <div class="col-lg-6">
            <h2 class="fw-bold">About the park</h2>
            <p>{{ config('murchison.summary') }}</p>
            <p class="small text-muted">Area: about {{ number_format(config('murchison.area_km2')) }} km² · Managed by the Uganda Wildlife Authority (UWA)</p>
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach (collect(config('murchison.activities'))->where('priority', '>=', 6) as $a)<span class="badge rounded-pill bg-light text-forest border">{{ $a['name'] }}</span>@endforeach
            </div>
            <a href="{{ route('park') }}" class="btn btn-outline-forest">More about the park</a>
        </div>
        <div class="col-lg-6">
            <div class="row g-3 text-center">
                <div class="col-6"><div class="card border-0 shadow-sm"><div class="card-body"><div class="fs-2 fw-bold text-forest">{{ $stats['activities'] }}</div><div class="small text-muted">activities planned</div></div></div></div>
                <div class="col-6"><div class="card border-0 shadow-sm"><div class="card-body"><div class="fs-2 fw-bold text-forest">{{ $stats['scenes'] }}</div><div class="small text-muted">360° scenes</div></div></div></div>
                <div class="col-6"><div class="card border-0 shadow-sm"><div class="card-body"><div class="fs-2 fw-bold text-forest">{{ $stats['operators'] }}</div><div class="small text-muted">approved tour operators</div></div></div></div>
                <div class="col-6"><div class="card border-0 shadow-sm"><div class="card-body"><div class="fs-2 fw-bold text-forest">{{ $stats['trips'] }}</div><div class="small text-muted">trips booked</div></div></div></div>
            </div>
        </div>
    </div>
</section>

@if ($packages->isNotEmpty())
<section class="container-xl pb-5">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <h2 class="fw-bold mb-0">Tour packages from approved operators</h2>
        <a href="{{ route('packages.index') }}" class="small">All packages →</a>
    </div>
    <div class="row g-4">
        @foreach ($packages as $package)
            <div class="col-md-6 col-lg-4">@include('packages._card')</div>
        @endforeach
    </div>
</section>
@endif

<section class="container-xl pb-5">
    <div class="card border-0 bg-forest text-white"><div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h2 class="h4 fw-bold mb-1">Ready to plan?</h2><p class="mb-0 opacity-75">It takes less than a minute to get a personalised itinerary and budget.</p></div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('plan.index') }}" class="btn btn-gold fw-semibold">Build my itinerary</a>
            <a href="{{ route('register', ['as' => 'operator']) }}" class="btn btn-outline-light">Register as a tour operator</a>
        </div>
    </div></div>
</section>
@endsection
