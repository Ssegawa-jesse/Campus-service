@extends('layouts.app')
@section('title', 'Smart Trip Planner')

@php
    $icons = ['gorillas' => 'emoji-sunglasses', 'chimpanzees' => 'tree', 'primates' => 'bug', 'big_game' => 'binoculars', 'birding' => 'feather', 'hiking' => 'signpost-2', 'boat' => 'water', 'waterfalls' => 'droplet-half', 'culture' => 'people', 'hot_springs' => 'thermometer-sun', 'walking_safari' => 'person-walking', 'remote' => 'compass'];
@endphp

@section('content')
<section class="bg-forest py-5">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">Smart trip planner</div>
        <h1 class="fw-bold">Which park is right for you?</h1>
        <p class="text-white-50 mb-0">Answer five quick questions and we'll rank all of Uganda's national parks for you – with reasons.</p>
    </div>
</section>

<div class="container-xl py-5">
    <form method="POST" action="{{ route('planner.results') }}" class="row g-4">
        @csrf
        <div class="col-12 planner-step">
            <h5><span class="badge bg-gold me-2">1</span>What would you love to experience? <small class="text-muted fw-normal">(choose one or more)</small></h5>
            <div class="row g-2 mt-1">
                @foreach (\App\Models\Park::INTERESTS as $key => $label)
                    <div class="col-6 col-md-4 col-lg-3 interest-option">
                        <input type="checkbox" name="interests[]" value="{{ $key }}" id="i-{{ $key }}" @checked(in_array($key, old('interests', [])))>
                        <label for="i-{{ $key }}"><i class="bi bi-{{ $icons[$key] ?? 'star' }}"></i> {{ $label }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-md-6 planner-step">
            <h5><span class="badge bg-gold me-2">2</span>How fit are you?</h5>
            @foreach ([1 => ['Relaxed', 'Short walks and game drives'], 2 => ['Active', 'A few hours of walking on uneven ground'], 3 => ['Adventurous', 'Steep, long hikes at altitude are fine']] as $v => [$t, $d])
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="fitness" value="{{ $v }}" id="f{{ $v }}" @checked(old('fitness', 2) == $v)>
                    <label class="form-check-label" for="f{{ $v }}"><strong>{{ $t }}</strong> <span class="text-muted small">– {{ $d }}</span></label>
                </div>
            @endforeach
        </div>

        <div class="col-md-6 planner-step">
            <h5><span class="badge bg-gold me-2">3</span>Budget per person (USD)</h5>
            <input type="range" class="form-range" min="50" max="3000" step="50" name="budget" id="budget" value="{{ old('budget', 600) }}">
            <div class="d-flex justify-content-between small text-muted"><span>$50</span><strong class="text-forest fs-5" id="budgetOut">$600</strong><span>$3,000+</span></div>
            <div class="small text-muted" id="budgetUgx"></div>
        </div>

        <div class="col-md-6 planner-step">
            <h5><span class="badge bg-gold me-2">4</span>When are you travelling?</h5>
            <select name="month" class="form-select">
                <option value="">Not sure yet</option>
                @foreach (month_names() as $n => $m)<option value="{{ $n }}" @selected(old('month', now()->addMonth()->month) == $n)>{{ $m }}</option>@endforeach
            </select>
        </div>

        <div class="col-md-6 planner-step">
            <h5><span class="badge bg-gold me-2">5</span>How many days do you have?</h5>
            <div class="input-group">
                <input type="number" name="days" class="form-control" min="1" max="60" value="{{ old('days', 4) }}">
                <span class="input-group-text">days</span>
                <input type="number" name="group_size" class="form-control" min="1" max="50" value="{{ old('group_size', 2) }}">
                <span class="input-group-text">people</span>
            </div>
        </div>

        <div class="col-12 text-center">
            <button class="btn btn-gold btn-lg px-5"><i class="bi bi-magic me-1"></i> Show my recommendations</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const b = document.getElementById('budget'), out = document.getElementById('budgetOut'), ugx = document.getElementById('budgetUgx');
const rate = {{ (float) setting('usd_rate', config('pearl360.usd_rate')) }};
const upd = () => { out.textContent = '$' + Number(b.value).toLocaleString() + (b.value >= 3000 ? '+' : ''); ugx.textContent = '≈ UGX ' + Math.round(b.value * rate).toLocaleString(); };
b.addEventListener('input', upd); upd();
</script>
@endpush
