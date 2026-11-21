@extends('layouts.app')
@section('title', 'Smart itinerary')
@php
    $c = config('murchison');
    $fmtUgx = fn ($n) => \App\Services\Murchison\TripCost::ugx($n);
    $fmtUsd = fn ($n) => \App\Services\Murchison\TripCost::usd($n);
@endphp

@section('content')
<section class="bg-forest py-5 no-print">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">Smart itinerary</div>
        <h1 class="fw-bold mb-2">Your personalised Murchison Falls trip</h1>
        <p class="mb-0 opacity-75" style="max-width:720px">Choose your dates, interests and budget. The planner matches the park's activities to you, times them for the best wildlife viewing and estimates the cost.</p>
    </div>
</section>

<div class="container-xl py-4">
    <div class="row g-4">
        {{-- ---------------- Form ---------------- --}}
        <div class="col-lg-4 no-print">
            <form method="GET" action="{{ route('plan.index') }}" class="card border-0 shadow-sm planner-form">
                <div class="card-body">
                    <input type="hidden" name="build" value="1">
                    <h2 class="h5 fw-bold mb-3">Trip details</h2>
                    @if ($error)<div class="alert alert-warning py-2">{{ $error }}</div>@endif
                    @if ($errors->any())<div class="alert alert-warning py-2">{{ $errors->first() }}</div>@endif
                    <div class="row g-2 mb-3">
                        <div class="col-7"><label class="form-label small fw-semibold" for="start_date">Arrival date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $form['start_date'] }}" min="{{ now()->toDateString() }}" required></div>
                        <div class="col-5"><label class="form-label small fw-semibold" for="days">Days</label>
                            <select class="form-select" id="days" name="days">@foreach (range(1, 6) as $d)<option value="{{ $d }}" @selected($form['days'] == $d)>{{ $d }} {{ $d > 1 ? 'days' : 'day' }}</option>@endforeach</select></div>
                        <div class="col-6"><label class="form-label small fw-semibold" for="adults">Adults</label>
                            <input type="number" class="form-control" id="adults" name="adults" min="1" max="30" value="{{ $form['adults'] }}"></div>
                        <div class="col-6"><label class="form-label small fw-semibold" for="children">Children (under 15)</label>
                            <input type="number" class="form-control" id="children" name="children" min="0" max="30" value="{{ $form['children'] }}"></div>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-semibold" for="residency">Visitor category (for park fees)</label>
                        <select class="form-select" id="residency" name="residency">@foreach ($c['residency'] as $k => $v)<option value="{{ $k }}" @selected($form['residency'] === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="mb-3"><label class="form-label small fw-semibold" for="origin">Starting from</label>
                        <select class="form-select" id="origin" name="origin">@foreach ($c['origins'] as $k => $o)<option value="{{ $k }}" @selected($form['origin'] === $k)>{{ $o['label'] }}</option>@endforeach</select></div>

                    <label class="form-label small fw-semibold">What interests you?</label>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach ($c['interests'] as $k => [$label, $icon])
                            <input type="checkbox" class="btn-check" name="interests[]" value="{{ $k }}" id="i-{{ $k }}" @checked(in_array($k, $form['interests'], true))>
                            <label class="btn btn-sm btn-outline-forest rounded-pill" for="i-{{ $k }}">{{ $icon }} {{ $label }}</label>
                        @endforeach
                    </div>

                    <label class="form-label small fw-semibold">Fitness level</label>
                    <div class="btn-group w-100 mb-3" role="group">
                        @foreach ([1 => 'Easy only', 2 => 'Moderate', 3 => 'Very active'] as $v => $l)
                            <input type="radio" class="btn-check" name="fitness" value="{{ $v }}" id="f{{ $v }}" @checked($form['fitness'] == $v)><label class="btn btn-sm btn-outline-forest" for="f{{ $v }}">{{ $l }}</label>
                        @endforeach
                    </div>
                    <label class="form-label small fw-semibold">Budget level</label>
                    <div class="btn-group w-100 mb-3" role="group">
                        @foreach ($c['tiers'] as $k => $t)
                            <input type="radio" class="btn-check" name="tier" value="{{ $k }}" id="t-{{ $k }}" @checked($form['tier'] === $k)><label class="btn btn-sm btn-outline-forest" for="t-{{ $k }}">{{ $t['label'] }}</label>
                        @endforeach
                    </div>
                    <label class="form-label small fw-semibold">Pace</label>
                    <div class="btn-group w-100 mb-3" role="group">
                        <input type="radio" class="btn-check" name="pace" value="relaxed" id="p-r" @checked($form['pace'] === 'relaxed')><label class="btn btn-sm btn-outline-forest" for="p-r">Relaxed (2 a day)</label>
                        <input type="radio" class="btn-check" name="pace" value="full" id="p-f" @checked($form['pace'] === 'full')><label class="btn btn-sm btn-outline-forest" for="p-f">Full (up to 3)</label>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-semibold" for="transport">Transport</label>
                        <select class="form-select" id="transport" name="transport">@foreach ($c['transport'] as $k => $t)<option value="{{ $k }}" @selected($form['transport'] === $k)>{{ $t['label'] }}</option>@endforeach</select></div>
                    <details class="mb-3">
                        <summary class="small fw-semibold" style="cursor:pointer">Must-do activities (optional)</summary>
                        <div class="mt-2">
                            @foreach ($c['activities'] as $k => $a)
                                <div class="form-check small"><input class="form-check-input" type="checkbox" name="must_do[]" value="{{ $k }}" id="m-{{ $k }}" @checked(in_array($k, $form['must_do'], true))><label class="form-check-label" for="m-{{ $k }}">{{ $a['name'] }}</label></div>
                            @endforeach
                        </div>
                    </details>
                    <button class="btn btn-gold w-100 fw-semibold"><i class="bi bi-magic"></i> Build my itinerary</button>
                </div>
            </form>

            @if ($saved->isNotEmpty())
                <div class="card border-0 shadow-sm mt-3"><div class="card-body">
                    <h3 class="h6 fw-bold">My saved itineraries</h3>
                    @foreach ($saved as $s)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1 small">
                            <a href="{{ route('plan.index', ['p' => $s->recommended_parks['code'] ?? '']) }}">{{ $s->title }}</a>
                            <form method="POST" action="{{ route('tourist.plans.destroy', $s) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0" aria-label="Delete"><i class="bi bi-trash"></i></button></form>
                        </div>
                    @endforeach
                </div></div>
            @endif
        </div>

        {{-- ---------------- Itinerary ---------------- --}}
        <div class="col-lg-8" id="itinerary">
            @if (! $plan)
                <div class="card border-0 shadow-sm text-center py-5"><div class="card-body">
                    <div class="display-5">🗺️</div>
                    <h2 class="h4 fw-bold">Your itinerary will appear here</h2>
                    <p class="text-muted mb-0">Fill in your trip details and press <strong>Build my itinerary</strong>.</p>
                </div></div>
            @else
                @php $persons = $plan['input']['adults'] + $plan['input']['children']; @endphp
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body py-2"><div class="small text-muted">Days</div><div class="fs-4 fw-bold text-forest">{{ $plan['input']['days'] }}</div></div></div></div>
                    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body py-2"><div class="small text-muted">Activities</div><div class="fs-4 fw-bold text-forest">{{ $plan['activity_count'] }}</div></div></div></div>
                    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body py-2"><div class="small text-muted">Estimated total</div><div class="fs-4 fw-bold text-forest">{{ $fmtUsd($plan['total']) }}</div><div class="small text-muted">{{ $fmtUgx($plan['total']) }}</div></div></div></div>
                    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body py-2"><div class="small text-muted">Per person</div><div class="fs-4 fw-bold text-forest">{{ $fmtUsd($plan['total'] / $persons) }}</div><div class="small text-muted">{{ $plan['season']['month'] }}: {{ strtolower($plan['season']['season']) }} season</div></div></div></div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3 no-print">
                    <a href="{{ route('tourist.bookings.create', ['plan' => $plan['code']]) }}" class="btn btn-gold"><i class="bi bi-send"></i> Book this itinerary</a>
                    @if (auth()->user()?->isTourist())
                        <form method="POST" action="{{ route('tourist.plans.store') }}">@csrf<input type="hidden" name="p" value="{{ $plan['code'] }}"><button class="btn btn-forest"><i class="bi bi-bookmark"></i> Save</button></form>
                    @elseif (! auth()->check())
                        <a href="{{ route('login') }}" class="btn btn-forest"><i class="bi bi-bookmark"></i> Log in to save</a>
                    @endif
                    <button type="button" class="btn btn-outline-forest" data-share="{{ route('plan.index', ['p' => $plan['code']]) }}"><i class="bi bi-link-45deg"></i> Share</button>
                    <button type="button" class="btn btn-outline-forest" onclick="window.print()"><i class="bi bi-printer"></i> Print / PDF</button>
                    <a href="{{ route('map', ['ids' => implode(',', $plan['attraction_ids'])]) }}" class="btn btn-outline-forest"><i class="bi bi-geo-alt"></i> Show on map</a>
                </div>

                @foreach ($plan['warnings'] as $w)<div class="alert alert-warning py-2 small">⚠️ {{ $w }}</div>@endforeach

                @foreach ($plan['days'] as $day)
                    <div class="card border-0 shadow-sm mb-3 day-card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <h3 class="h6 fw-bold mb-0">Day {{ $day['day'] }}: {{ $day['title'] }}</h3>
                                <span class="badge rounded-pill bg-light text-forest border" data-wx-date="{{ $day['date'] }}">{{ $plan['season']['season'] }} season</span>
                            </div>
                            <ul class="timeline mt-3 mb-0">
                                @foreach ($day['items'] as $it)
                                    @if ($it['kind'] === 'tip')
                                        <li class="tip"><div class="tip-box small">💡 {{ $it['detail'] }}</div></li>
                                    @else
                                        <li class="{{ $it['kind'] }}">
                                            @if (! empty($it['cost']))<span class="float-end small text-muted">{{ $fmtUsd($it['cost']) }}</span>@endif
                                            <div><span class="t-time">{{ $it['time'] }}</span><strong>{{ $it['title'] }}</strong></div>
                                            <div class="small text-muted">{{ $it['detail'] }}</div>
                                            @if (! empty($it['activity_id']) && isset($scenes[$it['activity_id']]))
                                                <a class="small no-print" href="{{ route('explore', ['scene' => $scenes[$it['activity_id']]]) }}"><i class="bi bi-badge-vr"></i> View in 360°</a>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100"><div class="card-body">
                            <h3 class="h6 fw-bold">Estimated budget</h3>
                            <table class="table table-sm small mb-2">
                                @foreach ($plan['costs'] as $l)
                                    <tr><td><strong>{{ $l['label'] }}</strong><div class="text-muted">{{ $l['detail'] }}</div></td><td class="text-end text-nowrap">{{ $fmtUsd($l['ugx']) }}<div class="text-muted">{{ $fmtUgx($l['ugx']) }}</div></td></tr>
                                @endforeach
                                <tr class="fw-bold"><td>Total</td><td class="text-end text-nowrap">{{ $fmtUsd($plan['total']) }}<div>{{ $fmtUgx($plan['total']) }}</div></td></tr>
                            </table>
                            <p class="small text-muted mb-1">Planning estimate. Confirm current UWA fees and lodge rates before booking.</p>
                            <a class="small no-print" href="{{ route('budget') }}">Adjust in the budget calculator →</a>
                        </div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100"><div class="card-body small">
                            <h3 class="h6 fw-bold">Why this plan</h3>
                            <ul class="ps-3">@foreach ($plan['reasons'] as $r)<li>{{ $r }}</li>@endforeach</ul>
                            @if ($plan['skipped'])
                                <h3 class="h6 fw-bold">Not included</h3>
                                <ul class="ps-3 text-muted">@foreach ($plan['skipped'] as $r)<li>{{ $r }}</li>@endforeach</ul>
                            @endif
                            <h3 class="h6 fw-bold">What to pack</h3>
                            <ul class="ps-3 mb-0">@foreach ($plan['packing'] as $r)<li>{{ $r }}</li>@endforeach</ul>
                        </div></div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>window.MF_WEATHER = @json(config('murchison.weather_point'));</script>
<script src="{{ asset('js/murchison.js') }}?v=1"></script>
@endpush
