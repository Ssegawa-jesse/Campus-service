@extends('layouts.app')
@section('title', $operator->company_name)

@section('content')
<div class="container-xl py-5">
    <div class="d-flex gap-4 align-items-center mb-4">
        <img src="{{ $operator->logoUrl() }}" width="96" height="96" class="rounded-4 shadow-sm" alt="">
        <div>
            <h1 class="fw-bold mb-1">{{ $operator->company_name }}</h1>
            <div class="small"><span class="text-forest"><i class="bi bi-patch-check-fill"></i> Verified by UWA admin</span>
                @if ($operator->licence_no) · Licence {{ $operator->licence_no }}@endif
                @if ($operator->reviews->isNotEmpty()) · @include('partials.stars', ['rating' => round($operator->reviews->avg('rating'), 1)])@endif
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <p>{!! nl2br(e($operator->about)) !!}</p>
            <h4 class="mt-4">Packages</h4>
            <div class="row g-4">
                @forelse ($operator->packages as $package)
                    @php $package->setRelation('operator', $operator); @endphp
                    <div class="col-md-6">@include('packages._card')</div>
                @empty
                    <p class="text-muted">No packages yet – you can still send a custom enquiry.</p>
                @endforelse
            </div>
            <h4 class="mt-5">Reviews</h4>
            @auth
                @if (auth()->user()->isTourist())
                    <form method="POST" action="{{ route('tourist.reviews.store') }}" class="row g-2 mb-3">
                        @csrf <input type="hidden" name="operator_id" value="{{ $operator->id }}">
                        <div class="col-md-3"><select name="rating" class="form-select">@for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ str_repeat('★', $i) }}</option>@endfor</select></div>
                        <div class="col-md-7"><input name="comment" class="form-control" placeholder="How was your trip with this operator?" required minlength="10"></div>
                        <div class="col-md-2 d-grid"><button class="btn btn-forest">Post</button></div>
                    </form>
                @endif
            @endauth
            @forelse ($operator->reviews as $review)
                <div class="border-bottom py-2"><strong>{{ $review->user->name }}</strong> @include('partials.stars', ['rating' => $review->rating])<p class="mb-0">{{ $review->comment }}</p></div>
            @empty
                <p class="text-muted">No reviews yet.</p>
            @endforelse
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5>Parks served</h5>
                    <div class="d-flex flex-wrap gap-1 mb-3">@foreach ($operator->parks as $p)<a href="{{ route('parks.show', $p) }}" class="chip">{{ $p->name }}</a>@endforeach</div>
                    @if ($operator->address)<p class="small mb-1"><i class="bi bi-geo-alt"></i> {{ $operator->address }}</p>@endif
                    @if ($operator->website)<p class="small mb-3"><i class="bi bi-globe"></i> <a href="{{ $operator->website }}" target="_blank" rel="noopener nofollow">{{ $operator->website }}</a></p>@endif
                    @auth
                        @if (auth()->user()->isTourist())
                            <a href="{{ route('tourist.bookings.create', ['operator' => $operator->id]) }}" class="btn btn-gold w-100">Send an enquiry</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-gold w-100">Log in to enquire</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
