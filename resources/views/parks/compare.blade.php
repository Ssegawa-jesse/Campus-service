@extends('layouts.app')
@section('title', 'Compare Parks')

@section('content')
<div class="container-xl py-5">
    <div class="section-kicker">Side by side</div>
    <h1 class="section-title mb-3">Compare parks</h1>
    <form class="row g-2 mb-4" method="GET">
        @for ($i = 0; $i < 4; $i++)
            <div class="col-6 col-md-3">
                <select name="parks[]" class="form-select">
                    <option value="">{{ $i < 2 ? 'Choose a park' : '+ Add another (optional)' }}</option>
                    @foreach ($all as $p)<option value="{{ $p->id }}" @selected(($selected[$i] ?? null) == $p->id)>{{ $p->name }}</option>@endforeach
                </select>
            </div>
        @endfor
        <div class="col-12"><button class="btn btn-forest"><i class="bi bi-layout-split me-1"></i> Compare</button></div>
    </form>

    <div class="table-responsive bg-white rounded-4 shadow-sm">
        <table class="table compare-table mb-0">
            <thead>
                <tr><th></th>@foreach ($parks as $p)<td><img src="{{ $p->coverUrl() }}" class="rounded w-100 mb-2" style="height:120px;object-fit:cover" alt=""><h5 class="mb-0"><a href="{{ route('parks.show', $p) }}">{{ $p->name }}</a></h5></td>@endforeach</tr>
            </thead>
            <tbody>
                <tr><th>Region</th>@foreach ($parks as $p)<td>{{ $p->region }}</td>@endforeach</tr>
                <tr><th>Highlights</th>@foreach ($parks as $p)<td>{{ $p->tagline }}</td>@endforeach</tr>
                <tr><th>Best for</th>@foreach ($parks as $p)<td>@foreach ($p->interestLabels() as $l)<span class="chip me-1 mb-1">{{ $l }}</span>@endforeach</td>@endforeach</tr>
                <tr><th>Difficulty</th>@foreach ($parks as $p)<td>{{ $p->difficultyLabel() }}</td>@endforeach</tr>
                <tr><th>Indicative cost</th>@foreach ($parks as $p)<td>from <strong>${{ number_format($p->min_cost) }}</strong><br><small class="text-muted">≈ {{ ugx(usd_to_ugx($p->min_cost)) }}</small></td>@endforeach</tr>
                <tr><th>Minimum stay</th>@foreach ($parks as $p)<td>{{ $p->min_days }} day(s)</td>@endforeach</tr>
                <tr><th>Distance from Kampala</th>@foreach ($parks as $p)<td>{{ $p->distance_from_kampala_km }} km</td>@endforeach</tr>
                <tr><th>Best time</th>@foreach ($parks as $p)<td>{{ $p->best_time }}</td>@endforeach</tr>
                <tr><th>Size</th>@foreach ($parks as $p)<td>{{ $p->area_km2 ? number_format($p->area_km2).' km²' : '–' }}</td>@endforeach</tr>
                <tr><th>Activities</th>@foreach ($parks as $p)<td><ul class="small ps-3 mb-0">@foreach ($p->activities as $a)<li>{{ $a->name }} <span class="text-muted">(${{ number_format($a->indicative_cost) }})</span></li>@endforeach</ul></td>@endforeach</tr>
                <tr><th>Rating</th>@foreach ($parks as $p)<td>@if ($p->reviews_avg_rating)@include('partials.stars', ['rating' => round($p->reviews_avg_rating, 1)]) {{ number_format($p->reviews_avg_rating, 1) }}@else – @endif</td>@endforeach</tr>
                <tr><th>UNESCO site</th>@foreach ($parks as $p)<td>{!! $p->is_unesco ? '<i class="bi bi-check-circle-fill text-forest"></i> Yes' : 'No' !!}</td>@endforeach</tr>
                <tr><th></th>@foreach ($parks as $p)<td><a href="{{ route('tour.show', $p) }}" class="btn btn-sm btn-forest"><i class="bi bi-badge-vr"></i> 360° tour ({{ $p->scenes_count }})</a></td>@endforeach</tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
