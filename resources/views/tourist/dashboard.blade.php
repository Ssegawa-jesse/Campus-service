@extends('layouts.app')
@section('title', 'My dashboard')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>Hello, {{ Str::words($user->name, 1, '') }} 👋</h1>
    <a href="{{ route('itinerary.index') }}" class="btn btn-gold"><i class="bi bi-calendar2-week"></i> New Smart Itinerary</a>
</div>

@foreach ($actionNeeded as $b)
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bell-fill me-1"></i> Quote received for <strong>{{ $b->park->name }}</strong> ({{ ugx($b->quoted_amount) }}).</span>
        <a href="{{ route('payments.checkout', $b) }}" class="btn btn-sm btn-forest">Pay now</a>
    </div>
@endforeach

<div class="row g-3 mb-4">
    @foreach ([['bookings', 'Bookings', 'calendar-check'], ['paid', 'Paid trips', 'cash-coin'], ['itineraries', 'Smart itineraries', 'calendar2-week'], ['reviews', 'Reviews written', 'star']] as [$k, $l, $i])
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

<h5>My Smart Itineraries</h5>
<div class="row g-3">
    @forelse ($itineraries as $it)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6><a href="{{ route('itinerary.show', $it) }}" class="text-reset">{{ $it->title }}</a></h6>
                    @unless ($it->bookings_count)
                        <form method="POST" action="{{ route('itinerary.destroy', $it) }}" data-confirm="Delete this itinerary?">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button></form>
                    @endunless
                </div>
                <div class="small text-muted mb-2">{{ $it->reference }} · {{ $it->start_date->format('d M Y') }} · {{ $it->days }} days · {{ config('murchison.categories')[$it->visitor_category]['label'] ?? '' }}</div>
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-forest">{{ ugx($it->total_ugx) }}</strong>
                    <span class="badge bg-{{ $it->status === 'booked' ? 'success' : 'secondary' }} text-capitalize">{{ $it->status }}</span>
                </div>
            </div></div>
        </div>
    @empty
        <p class="text-muted">No saved itineraries yet. <a href="{{ route('itinerary.index') }}">Build one now</a>.</p>
    @endforelse
</div>
@endsection
