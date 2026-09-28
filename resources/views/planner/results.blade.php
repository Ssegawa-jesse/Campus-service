@extends('layouts.app')
@section('title', 'Your Recommended Parks')

@section('content')
<section class="bg-forest py-5">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">Your personalised plan</div>
        <h1 class="fw-bold">Top {{ $results->count() }} parks for you</h1>
        <div class="d-flex flex-wrap gap-2 mt-2 small">
            @foreach ($prefs['interests'] as $i)<span class="badge bg-light text-dark">{{ \App\Models\Park::INTERESTS[$i] ?? $i }}</span>@endforeach
            <span class="badge bg-gold">{{ [1 => 'Relaxed', 2 => 'Active', 3 => 'Adventurous'][$prefs['fitness']] }}</span>
            @if (!empty($prefs['budget']))<span class="badge bg-gold">${{ number_format($prefs['budget']) }} pp</span>@endif
            @if (!empty($prefs['month']))<span class="badge bg-gold">{{ month_names()[$prefs['month']] }}</span>@endif
            @if (!empty($prefs['days']))<span class="badge bg-gold">{{ $prefs['days'] }} days</span>@endif
        </div>
    </div>
</section>

<div class="container-xl py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            @foreach ($results as $rank => $r)
                @php $park = $r['park']; @endphp
                <div class="card border-0 shadow-sm mb-3 overflow-hidden">
                    <div class="row g-0">
                        <div class="col-md-4" style="background:url('{{ $park->coverUrl() }}') center/cover;min-height:180px">
                            <span class="badge bg-dark m-2">#{{ $rank + 1 }}</span>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <div class="d-flex gap-3 align-items-start">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1"><a href="{{ route('parks.show', $park) }}" class="text-reset">{{ $park->name }}</a></h4>
                                        <p class="small text-muted mb-2">{{ $park->tagline }}</p>
                                    </div>
                                    <div class="score-ring" style="--p: {{ $r['score'] }}" title="Match score"><span>{{ $r['score'] }}%</span></div>
                                </div>
                                <ul class="list-unstyled small mb-2">
                                    @foreach ($r['reasons'] as $reason)<li><i class="bi bi-check-circle-fill text-forest me-1"></i>{{ $reason }}</li>@endforeach
                                    @foreach ($r['warnings'] as $w)<li><i class="bi bi-exclamation-circle-fill text-warning me-1"></i>{{ $w }}</li>@endforeach
                                </ul>
                                @if ($r['activities']->isNotEmpty())
                                    <div class="small mb-3"><strong>Suggested activities:</strong> {{ $r['activities']->pluck('name')->implode(' · ') }}</div>
                                @endif
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('tour.show', $park) }}" class="btn btn-sm btn-forest"><i class="bi bi-badge-vr"></i> 360° tour</a>
                                    <a href="{{ route('parks.show', $park) }}" class="btn btn-sm btn-outline-forest">Details</a>
                                    <a href="{{ route('packages.index', ['park' => $park->slug]) }}" class="btn btn-sm btn-outline-secondary">Packages</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3 sticky-top" style="top:90px">
                <div class="card-body">
                    <h5><i class="bi bi-bookmark-heart text-gold"></i> Save this plan</h5>
                    @auth
                        @if (auth()->user()->isTourist())
                            <form method="POST" action="{{ route('tourist.plans.store') }}">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input name="title" class="form-control mb-2" placeholder="e.g. Honeymoon safari" value="{{ $plan->title }}">
                                <button class="btn btn-forest w-100">Save to my dashboard</button>
                            </form>
                        @else
                            <p class="small text-muted mb-0">Plans can be saved by tourist accounts.</p>
                        @endif
                    @else
                        <p class="small text-muted">Create a free account to save plans, add favourites and book.</p>
                        <a href="{{ route('register') }}" class="btn btn-gold w-100">Sign up free</a>
                    @endauth
                    <hr>
                    <a href="{{ route('parks.compare', ['parks' => $results->take(3)->pluck('park.id')->all()]) }}" class="btn btn-outline-forest w-100 mb-2"><i class="bi bi-layout-split"></i> Compare top 3</a>
                    <a href="{{ route('planner.index') }}" class="btn btn-link w-100">Start over</a>
                    <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle"></i> Match score weights: interests 40%, fitness 20%, budget 15%, season 15%, days 10%.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
