@extends('layouts.app')
@section('title', 'My dashboard')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>Hello, {{ Str::words($user->name, 1, '') }} 👋</h1>
    <a href="{{ route('planner.index') }}" class="btn btn-gold"><i class="bi bi-magic"></i> Plan a new trip</a>
</div>

@foreach ($actionNeeded as $b)
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bell-fill me-1"></i> Quote received for <strong>{{ $b->park->name }}</strong> ({{ ugx($b->quoted_amount) }}).</span>
        <a href="{{ route('payments.checkout', $b) }}" class="btn btn-sm btn-forest">Pay now</a>
    </div>
@endforeach

<div class="row g-3 mb-4">
    @foreach ([['bookings', 'Bookings', 'calendar-check'], ['paid', 'Paid trips', 'cash-coin'], ['favourites', 'Saved parks', 'heart'], ['reviews', 'Reviews written', 'star']] as [$k, $l, $i])
        <div class="col-6 col-md-3"><div class="kpi"><i class="bi bi-{{ $i }}"></i><div class="value">{{ $stats[$k] }}</div><div class="label">{{ $l }}</div></div></div>
    @endforeach
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-2"><h5 class="mb-0">Recent bookings</h5><a href="{{ route('tourist.bookings.index') }}" class="small">View all</a></div>
        @forelse ($bookings as $booking)
            <a href="{{ route('tourist.bookings.show', $booking) }}" class="d-flex justify-content-between align-items-center border-bottom py-2 text-reset">
                <span><strong>{{ $booking->park->name }}</strong><br><span class="small text-muted">{{ $booking->reference }} · {{ $booking->operator->company_name }} · {{ $booking->travel_date->format('d M Y') }}</span></span>
                @include('partials.booking-status')
            </a>
        @empty
            <p class="text-muted mb-0">No bookings yet. <a href="{{ route('packages.index') }}">Browse safari packages</a>.</p>
        @endforelse
    </div>
</div>

<h5>Saved parks</h5>
<div class="row g-3 mb-4">
    @forelse ($favourites as $park)
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <img src="{{ $park->coverUrl() }}" class="card-img-top" style="height:110px;object-fit:cover" alt="">
                <div class="card-body py-2">
                    <a href="{{ route('parks.show', $park) }}" class="fw-semibold text-reset">{{ $park->name }}</a>
                    <div class="d-flex gap-2 mt-2">
                        <a href="{{ route('tour.show', $park) }}" class="btn btn-sm btn-forest">360°</a>
                        <form method="POST" action="{{ route('tourist.favourites.toggle', $park) }}">@csrf<button class="btn btn-sm btn-outline-danger"><i class="bi bi-heart-fill"></i></button></form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <p class="text-muted">Tap <i class="bi bi-heart"></i> Save on any park to keep it here.</p>
    @endforelse
</div>

<h5>My trip plans</h5>
<div class="row g-3">
    @forelse ($plans as $plan)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h6>{{ $plan->title }}</h6>
                        <form method="POST" action="{{ route('tourist.plans.destroy', $plan) }}" data-confirm="Delete this plan?">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button></form>
                    </div>
                    <div class="small text-muted mb-2">{{ $plan->created_at->format('d M Y') }} · {{ collect($plan->preferences['interests'] ?? [])->map(fn ($i) => \App\Models\Park::INTERESTS[$i] ?? $i)->implode(', ') }}</div>
                    <ol class="small mb-2 ps-3">@foreach (array_slice($plan->recommended_parks, 0, 3) as $r)<li>{{ $r['name'] }} <span class="text-muted">({{ $r['score'] }}%)</span></li>@endforeach</ol>
                    <form method="POST" action="{{ route('planner.results') }}">@csrf
                        @foreach ($plan->preferences['interests'] ?? [] as $i)<input type="hidden" name="interests[]" value="{{ $i }}">@endforeach
                        @foreach (['fitness', 'budget', 'days', 'month'] as $k)@if (isset($plan->preferences[$k]))<input type="hidden" name="{{ $k }}" value="{{ $plan->preferences[$k] }}">@endif @endforeach
                        <button class="btn btn-sm btn-outline-forest">Re-run plan</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <p class="text-muted">Use the <a href="{{ route('planner.index') }}">trip planner</a> and save your results.</p>
    @endforelse
</div>
@endsection
