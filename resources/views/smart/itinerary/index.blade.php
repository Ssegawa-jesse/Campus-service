@extends('layouts.app')
@section('title', 'Smart Itinerary')

@section('content')
<section class="bg-forest py-5">
    <div class="container-xl">
        <div class="section-kicker text-gold">Module 1 · Primary target</div>
        <h1 class="fw-bold">Smart Itinerary for Murchison Falls</h1>
        <p class="text-white-50 mb-0">Tell us your dates, group, UWA visitor category and interests. We build a timed day-by-day plan with travel, ferry crossings, stays and a full UWA budget.</p>
    </div>
</section>

<div class="container-xl py-4">
    @if (count($mustDo))
        <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2">
            <i class="bi bi-pin-angle"></i> <strong>Must-do activities you added:</strong>
            @foreach ($activities->whereIn('slug', $mustDo) as $a)
                <form method="POST" action="{{ route('itinerary.remove', $a) }}" class="d-inline">@csrf
                    <button class="badge bg-dark border-0">{{ $a->name }} <i class="bi bi-x"></i></button>
                </form>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('itinerary.generate') }}" class="row g-4">
        @csrf
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4"><div class="card-body row g-3">
                <h5 class="col-12 mb-0"><span class="badge bg-gold me-2">1</span>Trip basics</h5>
                <div class="col-md-4"><label class="form-label small">Start date</label><input type="date" name="start_date" class="form-control" min="{{ now()->toDateString() }}" value="{{ old('start_date', $defaults['start_date']) }}" required></div>
                <div class="col-md-2 col-4"><label class="form-label small">Days</label><input type="number" name="days" min="1" max="7" class="form-control" value="{{ old('days', $defaults['days']) }}" required></div>
                <div class="col-md-3 col-4"><label class="form-label small">Adults</label><input type="number" name="adults" min="1" max="30" class="form-control" value="{{ old('adults', $defaults['adults']) }}" required></div>
                <div class="col-md-3 col-4"><label class="form-label small">Children (5–15)</label><input type="number" name="children" min="0" max="30" class="form-control" value="{{ old('children', $defaults['children']) }}"></div>
                <div class="col-md-6"><label class="form-label small">UWA visitor category</label>
                    <select name="category" class="form-select">@foreach (config('murchison.categories') as $k => $c)<option value="{{ $k }}" @selected(old('category', $defaults['category']) === $k)>{{ $c['label'] }} ({{ $c['currency'] }})</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label small">Starting from</label>
                    <select name="origin" class="form-select">@foreach (config('murchison.origins') as $k => $o)<option value="{{ $k }}" @selected(old('origin', $defaults['origin']) === $k)>{{ $o['label'] }} ({{ $o['km'] }} km, {{ $o['hours'] }} h)</option>@endforeach</select></div>
            </div></div>

            <div class="card border-0 shadow-sm mb-4"><div class="card-body">
                <h5><span class="badge bg-gold me-2">2</span>What do you love?</h5>
                <div class="row g-2">
                    @foreach (config('murchison.interests') as $k => $label)
                        <div class="col-6 col-md-4 interest-option">
                            <input type="checkbox" name="interests[]" value="{{ $k }}" id="i-{{ $k }}" @checked(in_array($k, old('interests', $defaults['interests'])))>
                            <label for="i-{{ $k }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div></div>

            <div class="card border-0 shadow-sm"><div class="card-body">
                <h5><span class="badge bg-gold me-2">3</span>Must-do activities <small class="text-muted fw-normal">(always scheduled when there is time)</small></h5>
                @foreach ($activities as $a)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="must_do[]" value="{{ $a->slug }}" id="m-{{ $a->slug }}" @checked(in_array($a->slug, old('must_do', $defaults['must_do'] ?? [])))>
                        <label class="form-check-label small" for="m-{{ $a->slug }}"><strong>{{ $a->name }}</strong> <span class="text-muted">– {{ $a->hours }} h, {{ $a->fitnessLabel() }}, {{ $a->priceLabel('fnr') }} / {{ $a->priceLabel('eac') }}</span></label>
                    </div>
                @endforeach
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-lg-top" style="top:90px"><div class="card-body row g-3">
                <h5 class="col-12 mb-0"><span class="badge bg-gold me-2">4</span>Style & budget</h5>
                <div class="col-12"><label class="form-label small">Fitness</label>
                    <select name="fitness" class="form-select">@foreach ([1 => 'Relaxed – short walks only', 2 => 'Active – a few hours walking', 3 => 'Adventurous'] as $v => $l)<option value="{{ $v }}" @selected(old('fitness', $defaults['fitness']) == $v)>{{ $l }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label small">Pace</label>
                    <select name="pace" class="form-select"><option value="full" @selected(old('pace', $defaults['pace']) === 'full')>Full days (up to 3 activities)</option><option value="relaxed" @selected(old('pace', $defaults['pace']) === 'relaxed')>Relaxed (up to 2 a day)</option></select></div>
                <div class="col-12"><label class="form-label small">Accommodation</label>
                    <select name="tier" class="form-select">@foreach (config('murchison.tiers') as $k => $t)<option value="{{ $k }}" @selected(old('tier', $defaults['tier']) === $k)>{{ $t['label'] }} (≈US${{ $t['usd_per_person_night'] }} pp/night)</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label small">Transport</label>
                    <select name="transport" class="form-select">@foreach (config('murchison.transport') as $k => $t)<option value="{{ $k }}" @selected(old('transport', $defaults['transport']) === $k)>{{ $t['label'] }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label small">Total budget limit (UGX, optional)</label><input type="number" name="budget_limit" min="0" step="10000" class="form-control" value="{{ old('budget_limit', $defaults['budget_limit']) }}" placeholder="e.g. 4000000"></div>
                <div class="col-12"><button class="btn btn-gold btn-lg w-100"><i class="bi bi-magic me-1"></i> Generate my itinerary</button></div>
                <p class="small text-muted mb-0">Not sure? <a href="{{ route('assistant') }}">Ask the AI Travel Assistant</a> or <a href="{{ route('explore') }}">preview activities in 360°</a>.</p>
            </div></div>
        </div>
    </form>
</div>
@endsection
