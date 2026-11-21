@extends('layouts.app')
@section('title', 'Travel Budget Calculator')

@section('content')
<section class="bg-forest py-5">
    <div class="container-xl">
        <div class="section-kicker text-gold">Module 2</div>
        <h1 class="fw-bold">Travel Budget Calculator</h1>
        <p class="text-white-50 mb-0">Uses UWA tiered pricing: Foreign Non-Resident (USD), Foreign Resident (USD) and East African Citizen (UGX). Covers park entry, activity permits, accommodation, transport, vehicle entry and the Paraa ferry.</p>
    </div>
</section>
<div class="container-xl py-4">
    <div class="row g-4">
        <div class="col-lg-5">
            <form method="GET" action="{{ route('budget') }}" class="card border-0 shadow-sm" id="budgetForm"><div class="card-body row g-3">
                <div class="col-12"><label class="form-label small">UWA visitor category</label>
                    <div class="btn-group w-100" role="group">
                        @foreach (config('murchison.categories') as $k => $c)
                            <input type="radio" class="btn-check" name="category" id="c-{{ $k }}" value="{{ $k }}" @checked($in['category'] === $k)>
                            <label class="btn btn-outline-forest btn-sm" for="c-{{ $k }}">{{ $c['label'] }}<br><small>{{ $c['currency'] }}</small></label>
                        @endforeach
                    </div></div>
                <div class="col-4"><label class="form-label small">Days</label><input type="number" name="days" min="1" max="14" value="{{ $in['days'] }}" class="form-control"></div>
                <div class="col-4"><label class="form-label small">Adults</label><input type="number" name="adults" min="1" max="30" value="{{ $in['adults'] }}" class="form-control"></div>
                <div class="col-4"><label class="form-label small">Children</label><input type="number" name="children" min="0" max="30" value="{{ $in['children'] }}" class="form-control"></div>
                <div class="col-6"><label class="form-label small">Accommodation</label><select name="tier" class="form-select">@foreach (config('murchison.tiers') as $k => $t)<option value="{{ $k }}" @selected($in['tier'] === $k)>{{ $t['label'] }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label small">Transport</label><select name="transport" class="form-select">@foreach (config('murchison.transport') as $k => $t)<option value="{{ $k }}" @selected($in['transport'] === $k)>{{ $t['label'] }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label small">Starting from</label><select name="origin" class="form-select">@foreach (config('murchison.origins') as $k => $o)<option value="{{ $k }}" @selected($in['origin'] === $k)>{{ $o['label'] }} ({{ $o['km'] }} km)</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label small">Activities</label>
                    @foreach ($activities as $a)
                        <div class="form-check small"><input class="form-check-input" type="checkbox" name="activities[]" value="{{ $a->slug }}" id="a-{{ $a->slug }}" @checked(in_array($a->slug, $in['activities']))>
                            <label class="form-check-label" for="a-{{ $a->slug }}">{{ $a->name }} <span class="text-muted">({{ $a->priceLabel($in['category']) }}{{ $a->group_fee_usd > 0 ? ' + guide/vehicle' : '' }})</span></label></div>
                    @endforeach
                </div>
                <div class="col-12"><button class="btn btn-gold w-100"><i class="bi bi-calculator"></i> Recalculate</button></div>
            </div></form>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4"><div class="card-body">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div><h5 class="mb-0">Estimated trip cost</h5><div class="small text-muted">{{ $result['category_label'] }} · {{ $in['days'] }} day(s) · {{ $in['adults'] + $in['children'] }} traveller(s)</div></div>
                    <div class="text-end"><div class="fs-3 fw-bold text-forest">{{ ugx($result['total']) }}</div><div class="small text-muted">≈ US${{ number_format($result['total_usd']) }} · {{ ugx($result['per_person']) }} pp</div></div>
                </div>
                <table class="table table-sm align-middle">
                    @foreach ($result['lines'] as $l)
                        @php $pct = $result['total'] ? round($l['ugx'] / $result['total'] * 100) : 0; @endphp
                        <tr><td><strong>{{ $l['label'] }}</strong><div class="small text-muted">{{ $l['detail'] }}</div><div class="progress mt-1" style="height:4px"><div class="progress-bar bg-success" style="width: {{ $pct }}%"></div></div></td><td class="text-end text-nowrap">{{ ugx($l['ugx']) }}</td></tr>
                    @endforeach
                    <tr><td>Contingency (10%)</td><td class="text-end">{{ ugx($result['contingency']) }}</td></tr>
                    <tr class="table-light fw-bold"><td>Total</td><td class="text-end">{{ ugx($result['total']) }}</td></tr>
                </table>
                <a href="{{ route('itinerary.index') }}" class="btn btn-outline-forest btn-sm"><i class="bi bi-calendar2-week"></i> Turn this into a Smart Itinerary</a>
            </div></div>

            <div class="card border-0 shadow-sm"><div class="card-body">
                <h5>UWA tariff used (per person)</h5>
                <div class="table-responsive"><table class="table table-sm small price-table mb-0">
                    <thead><tr><th>Item</th><th>Foreign Non-Resident</th><th>Foreign Resident</th><th>East African Citizen</th></tr></thead>
                    <tr><td>Park entry (24 h)</td><td>US${{ config('murchison.entry.fnr') }}</td><td>US${{ config('murchison.entry.fr') }}</td><td>UGX {{ number_format(config('murchison.entry.eac')) }}</td></tr>
                    @foreach ($activities as $a)
                        <tr><td>{{ $a->name }}</td><td>{{ $a->priceLabel('fnr') }}</td><td>{{ $a->priceLabel('fr') }}</td><td>{{ $a->priceLabel('eac') }}</td></tr>
                    @endforeach
                </table></div>
                <p class="small text-muted mt-2 mb-0">Exchange rate US$1 = UGX {{ number_format($result['rate']) }}. Children pay about half the entry fee; chimpanzee trekking is 12+ only. Indicative figures – confirm with UWA.</p>
            </div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>document.querySelectorAll('#budgetForm input, #budgetForm select').forEach(el => el.addEventListener('change', () => document.getElementById('budgetForm').submit()));</script>
@endpush
