@extends('layouts.app')
@section('title', 'Travel updates')
@php $c = config('murchison'); @endphp

@section('content')
<section class="bg-forest py-5">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">Travel updates</div>
        <h1 class="fw-bold mb-2">Weather and travel information</h1>
        <p class="mb-0 opacity-75" style="max-width:720px">The live forecast at Paraa, the best months to visit and important information before you travel.</p>
    </div>
</section>
<div class="container-xl py-4">
    <h2 class="h4 fw-bold">Weather this week</h2>
    <div class="row row-cols-2 row-cols-sm-4 row-cols-lg-7 g-2" id="mf-forecast"><div class="col-12 text-muted">Loading the forecast for {{ $c['weather_point']['label'] }}…</div></div>
    <p class="small text-muted mt-2">Forecast for {{ $c['weather_point']['label'] }}. <span id="mf-forecast-note"></span> Data: Open-Meteo.</p>

    <h2 class="h4 fw-bold mt-4">When to visit</h2>
    <p class="small text-muted">General pattern; individual years differ. <span class="badge bg-warning text-dark">Dry</span> <span class="badge bg-info text-dark">Wet</span></p>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-2">
        @foreach ($c['months'] as [$m, $season, $note])
            <div class="col"><div class="month-tile {{ strtolower($season) }}"><strong>{{ $m }}</strong><div class="text-muted">{{ $note }}</div></div></div>
        @endforeach
    </div>

    <h2 class="h4 fw-bold mt-4">Before you travel</h2>
    <div class="row g-3">
        @foreach ($c['notices'] as [$title, $body])
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h3 class="h6 fw-bold">{{ $title }}</h3><p class="small text-muted mb-0">{{ $body }}</p></div></div></div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>window.MF_WEATHER = @json($c['weather_point']);</script>
<script src="{{ asset('js/murchison.js') }}?v=1"></script>
@endpush
