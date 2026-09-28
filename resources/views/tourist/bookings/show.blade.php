@extends('layouts.app')
@section('title', 'Booking '.$booking->reference)
@section('dashboard', true)

@php
    $order = ['pending' => 0, 'quoted' => 1, 'paid' => 2, 'confirmed' => 3, 'completed' => 4];
    $pos = $order[$booking->status] ?? -1;
@endphp

@section('content')
<div class="page-head">
    <h1>Booking {{ $booking->reference }}</h1>
    @include('partials.booking-status')
</div>

@if ($pos >= 0)
<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <div class="steps">
        @foreach (['Enquiry sent', 'Quote received', 'Paid', 'Confirmed', 'Completed'] as $i => $label)
            <div class="step {{ $pos >= $i ? 'done' : '' }}"><div class="dot"><i class="bi bi-check"></i></div>{{ $label }}</div>
        @endforeach
    </div>
</div></div>
@endif

<div class="row g-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5>{{ $booking->park->name }}</h5>
                <dl class="row small mb-0">
                    <dt class="col-5">Operator</dt><dd class="col-7"><a href="{{ route('operators.show', $booking->operator) }}">{{ $booking->operator->company_name }}</a><br><span class="text-muted">{{ $booking->operator->contact_phone }}</span></dd>
                    @if ($booking->package)<dt class="col-5">Package</dt><dd class="col-7">{{ $booking->package->title }}</dd>@endif
                    <dt class="col-5">Travel date</dt><dd class="col-7">{{ $booking->travel_date->format('l, d M Y') }}</dd>
                    <dt class="col-5">Travellers</dt><dd class="col-7">{{ $booking->group_size }}</dd>
                    <dt class="col-5">Your message</dt><dd class="col-7">{{ $booking->message ?: '–' }}</dd>
                    <dt class="col-5">Sent</dt><dd class="col-7">{{ $booking->created_at->format('d M Y H:i') }}</dd>
                </dl>
            </div>
        </div>
        @if ($booking->operator_response)
            <div class="card border-0 shadow-sm mb-4 border-start border-4 border-success">
                <div class="card-body">
                    <h6><i class="bi bi-chat-left-quote text-forest"></i> Operator response <small class="text-muted fw-normal">{{ $booking->responded_at?->diffForHumans() }}</small></h6>
                    <p class="mb-0">{{ $booking->operator_response }}</p>
                </div>
            </div>
        @endif

        @if ($booking->status === 'completed' && ! $hasReviewed)
            <form method="POST" action="{{ route('tourist.reviews.store') }}" class="card border-0 shadow-sm">
                @csrf <input type="hidden" name="park_id" value="{{ $booking->park_id }}">
                <div class="card-body">
                    <h6>How was {{ $booking->park->name }}?</h6>
                    <select name="rating" class="form-select mb-2">@for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ str_repeat('★', $i) }}</option>@endfor</select>
                    <textarea name="comment" class="form-control mb-2" rows="3" placeholder="Tell other travellers about your visit" required minlength="10"></textarea>
                    <button class="btn btn-forest">Submit review</button>
                </div>
            </form>
        @endif
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6>Payment</h6>
                @if ($booking->quoted_amount)
                    <div class="fs-3 fw-bold text-forest">{{ ugx($booking->quoted_amount) }}</div>
                @else
                    <p class="text-muted small">Waiting for the operator to send a quote. You'll get an SMS and email.</p>
                @endif
                @if ($booking->canBePaid())
                    <a href="{{ route('payments.checkout', $booking) }}" class="btn btn-gold w-100 btn-lg mt-2"><i class="bi bi-phone"></i> Pay with Mobile Money / Card</a>
                @endif
                @foreach ($booking->payments as $p)
                    <div class="border-top mt-3 pt-2 small">
                        <div class="d-flex justify-content-between"><span>{{ $p->methodLabel() }}</span><span class="badge bg-{{ $p->status === 'successful' ? 'success' : ($p->status === 'pending' ? 'secondary' : 'danger') }}">{{ ucfirst($p->status) }}</span></div>
                        <div class="text-muted">{{ ugx($p->amount) }} · {{ $p->tx_ref }} · {{ $p->created_at->format('d M Y H:i') }}</div>
                    </div>
                @endforeach
                @if ($booking->isPaid())
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary w-100 mt-3"><i class="bi bi-printer"></i> Print receipt</button>
                @endif
            </div>
        </div>
        @if (in_array($booking->status, ['pending', 'quoted']))
            <form method="POST" action="{{ route('tourist.bookings.cancel', $booking) }}" data-confirm="Cancel this booking?">@csrf
                <button class="btn btn-outline-danger w-100">Cancel booking</button>
            </form>
        @endif
    </div>
</div>
@endsection
