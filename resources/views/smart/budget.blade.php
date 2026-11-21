@extends('layouts.app')
@section('title', 'Travel budget')
@php
    $c = config('murchison');
    $ugx = fn ($n) => \App\Services\Murchison\TripCost::ugx($n);
    $usd = fn ($n) => \App\Services\Murchison\TripCost::usd($n);
    $persons = $in['adults'] + $in['children'];
    $max = max(array_column($budget['lines'], 'ugx') ?: [1]) ?: 1;
@endphp

@section('content')
<section class="bg-forest py-5">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">Travel budgeting</div>
        <h1 class="fw-bold mb-2">Estimate your trip expenditure</h1>
        <p class="mb-0 opacity-75" style="max-width:720px">Change the number of days, people, accommodation and activities to see what a visit to Murchison Falls is likely to cost, in US dollars and Uganda shillings.</p>
    </div>
</section>
<div class="container-xl py-4">
    <div class="row g-4">
        <div class="col-lg-6">
            <form method="GET" class="card border-0 shadow-sm" id="budget-form"><div class="card-body">
                <h2 class="h5 fw-bold">Your trip</h2>
                <div class="row g-2 mb-3">
                    <div class="col-4"><label class="form-label small fw-semibold" for="b-days">Days</label><input class="form-control" type="number" id="b-days" name="days" min="1" max="14" value="{{ $in['days'] }}"></div>
                    <div class="col-4"><label class="form-label small fw-semibold" for="b-ad">Adults</label><input class="form-control" type="number" id="b-ad" name="adults" min="1" max="30" value="{{ $in['adults'] }}"></div>
                    <div class="col-4"><label class="form-label small fw-semibold" for="b-ch">Children</label><input class="form-control" type="number" id="b-ch" name="children" min="0" max="30" value="{{ $in['children'] }}"></div>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold" for="b-res">Visitor category</label>
                    <select class="form-select" id="b-res" name="residency">@foreach ($c['residency'] as $k => $v)<option value="{{ $k }}" @selected($in['residency'] === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="b-tier">Accommodation</label>
                        <select class="form-select" id="b-tier" name="tier">@foreach ($c['tiers'] as $k => $t)<option value="{{ $k }}" @selected($in['tier'] === $k)>{{ $t['label'] }} (≈US${{ $t['per_person_night_usd'] }}/person/night)</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="b-or">Starting from</label>
                        <select class="form-select" id="b-or" name="origin">@foreach ($c['origins'] as $k => $o)<option value="{{ $k }}" @selected($in['origin'] === $k)>{{ $o['label'] }} ({{ $o['km_to_gate'] }} km to the gate)</option>@endforeach</select></div>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold" for="b-tr">Transport</label>
                    <select class="form-select" id="b-tr" name="transport">@foreach ($c['transport'] as $k => $t)<option value="{{ $k }}" @selected($in['transport'] === $k)>{{ $t['label'] }}</option>@endforeach</select>
                    <div class="form-text">{{ $c['transport'][$in['transport']]['note'] }}</div></div>
                <label class="form-label small fw-semibold">Activities</label>
                @foreach ($c['activities'] as $k => $a)
                    <div class="form-check d-flex justify-content-between border-bottom py-1 small">
                        <span><input class="form-check-input" type="checkbox" name="activities[]" value="{{ $k }}" id="a-{{ $k }}" @checked(in_array($k, $in['activity_ids'], true))>
                            <label class="form-check-label" for="a-{{ $k }}">{{ $a['name'] }}</label></span>
                        <span class="text-muted">{{ $usd(\App\Services\Murchison\TripCost::activityCost($k, $in['adults'], $in['children'], $in['residency'])) }}</span>
                    </div>
                @endforeach
                <button class="btn btn-gold w-100 mt-3">Update estimate</button>
            </div></form>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="position:sticky;top:90px"><div class="card-body">
                <h2 class="h5 fw-bold">Estimated expenditure</h2>
                <div class="row g-2 mb-3">
                    <div class="col-6"><div class="p-3 bg-light rounded-3"><div class="small text-muted">Total</div><div class="fs-4 fw-bold text-forest">{{ $usd($budget['total']) }}</div><div class="small text-muted">{{ $ugx($budget['total']) }}</div></div></div>
                    <div class="col-6"><div class="p-3 bg-light rounded-3"><div class="small text-muted">Per person</div><div class="fs-4 fw-bold text-forest">{{ $usd($budget['total'] / $persons) }}</div><div class="small text-muted">{{ $ugx($budget['total'] / $persons) }}</div></div></div>
                </div>
                <table class="table table-sm small">
                    @foreach ($budget['lines'] as $l)
                        <tr><td><strong>{{ $l['label'] }}</strong><div class="text-muted">{{ $l['detail'] }}</div>
                            <div class="progress mt-1" style="height:6px"><div class="progress-bar bg-success" style="width: {{ round($l['ugx'] / $max * 100) }}%"></div></div></td>
                            <td class="text-end text-nowrap">{{ $usd($l['ugx']) }}<div class="text-muted">{{ $ugx($l['ugx']) }}</div></td></tr>
                    @endforeach
                    <tr class="fw-bold"><td>Total</td><td class="text-end">{{ $usd($budget['total']) }}</td></tr>
                </table>
                <p class="small text-muted mb-0">Rate used: US$1 = UGX {{ number_format($c['ugx_per_usd']) }}. All figures are planning estimates; confirm current UWA tariffs and lodge prices before paying.</p>
            </div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Recalculate as soon as a choice changes.
    document.querySelectorAll('#budget-form select, #budget-form input[type=checkbox]').forEach(function (el) {
        el.addEventListener('change', function () { document.getElementById('budget-form').submit(); });
    });
</script>
@endpush
