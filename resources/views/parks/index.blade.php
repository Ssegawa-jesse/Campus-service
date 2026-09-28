@extends('layouts.app')
@section('title', 'All National Parks')

@section('content')
<section class="bg-forest py-5">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">Uganda Wildlife Authority protected areas</div>
        <h1 class="fw-bold mb-3">Explore {{ $parks->count() }} parks</h1>
        <form class="row g-2 bg-white rounded-4 p-2 shadow" method="GET">
            <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control border-0" placeholder="Search by name or keyword"></div>
            <div class="col-6 col-md-2">
                <select name="region" class="form-select border-0"><option value="">Any region</option>@foreach ($regions as $r)<option @selected(request('region') === $r)>{{ $r }}</option>@endforeach</select>
            </div>
            <div class="col-6 col-md-2">
                <select name="interest" class="form-select border-0"><option value="">Any interest</option>@foreach (\App\Models\Park::INTERESTS as $k => $v)<option value="{{ $k }}" @selected(request('interest') === $k)>{{ $v }}</option>@endforeach</select>
            </div>
            <div class="col-6 col-md-2">
                <select name="sort" class="form-select border-0">
                    @foreach (['name' => 'A – Z', 'popular' => 'Most popular', 'cost' => 'Lowest cost', 'distance' => 'Nearest to Kampala'] as $k => $v)<option value="{{ $k }}" @selected(request('sort', 'name') === $k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2 d-grid"><button class="btn btn-gold"><i class="bi bi-funnel"></i> Filter</button></div>
        </form>
    </div>
</section>

<div class="container-xl py-5">
    @if ($parks->isEmpty())
        <div class="text-center py-5 text-muted"><i class="bi bi-search fs-1"></i><p class="mt-2">No parks match your search. <a href="{{ route('parks.index') }}">Clear filters</a></p></div>
    @endif
    <div class="row g-4">
        @foreach ($parks as $park)
            <div class="col-sm-6 col-lg-4">@include('partials.park-card')</div>
        @endforeach
    </div>
</div>
@endsection
