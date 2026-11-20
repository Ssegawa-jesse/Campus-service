@extends('layouts.app')
@section('title', $package->title)

@section('content')
<div class="container-xl py-5">
    <div class="row g-5">
        <div class="col-lg-8">
            <img src="{{ $package->imageUrl() }}" class="w-100 rounded-4 mb-4" style="max-height:420px;object-fit:cover" alt="">
            <div class="small text-muted"><a href="{{ route('parks.show', $package->park) }}"><i class="bi bi-geo-alt"></i> {{ $package->park->name }}</a></div>
            <h1 class="fw-bold">{{ $package->title }}</h1>
            <p class="lead-sm">{!! nl2br(e($package->description)) !!}</p>
            @if ($package->includes)
                <h5 class="mt-4">What's included</h5>
                <ul>@foreach (preg_split('/\r?\n/', $package->includes) as $line)@if (trim($line))<li>{{ $line }}</li>@endif @endforeach</ul>
            @endif
            <a href="{{ route('tour.show', $package->park) }}" class="btn btn-outline-forest mt-2"><i class="bi bi-badge-vr"></i> Preview {{ $package->park->name }} in 360°</a>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:90px">
                <div class="card-body">
                    <div class="fs-3 fw-bold text-forest">{{ ugx($package->price_ugx) }}</div>
                    <div class="small text-muted mb-3">per person · {{ $package->days }} days · max {{ $package->max_group }} people</div>
                    @auth
                        @if (auth()->user()->isTourist())
                            <a href="{{ route('tourist.bookings.create', ['package' => $package->slug]) }}" class="btn btn-gold w-100 btn-lg">Request to book</a>
                        @else
                            <p class="small text-muted">Log in with a tourist account to book.</p>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-gold w-100 btn-lg">Log in to book</a>
                    @endauth
                    <p class="small text-muted mt-2 mb-0"><i class="bi bi-shield-check"></i> The operator confirms availability and sends a final quote. Pay with MTN MoMo, Airtel Money or card.</p>
                    <hr>
                    <a href="{{ route('operators.show', $package->operator) }}" class="d-flex gap-2 align-items-center text-reset">
                        <img src="{{ $package->operator->logoUrl() }}" width="48" height="48" class="rounded" alt="">
                        <div><div class="fw-semibold">{{ $package->operator->company_name }}</div><div class="small text-forest"><i class="bi bi-patch-check-fill"></i> Verified operator</div></div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @if ($more->isNotEmpty())
        <h4 class="mt-5 mb-3">More from {{ $package->operator->company_name }}</h4>
        <div class="row g-4">@foreach ($more as $package)<div class="col-md-4">@include('packages._card')</div>@endforeach</div>
    @endif
</div>
@endsection
