@extends('layouts.app')
@section('title', 'Safari Packages')

@section('content')
<section class="bg-forest py-5">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">Verified tour operators</div>
        <h1 class="fw-bold mb-3">Safari packages</h1>
        <form class="row g-2 bg-white rounded-4 p-2 shadow" method="GET">
            <div class="col-md-4"><select name="park" class="form-select border-0"><option value="">Any park</option>@foreach ($parks as $p)<option value="{{ $p->slug }}" @selected(request('park') === $p->slug)>{{ $p->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><input type="number" name="max_price" class="form-control border-0" placeholder="Max price (UGX)" value="{{ request('max_price') }}"></div>
            <div class="col-md-3"><input type="number" name="days" class="form-control border-0" placeholder="Max days" value="{{ request('days') }}"></div>
            <div class="col-md-2 d-grid"><button class="btn btn-gold">Search</button></div>
        </form>
    </div>
</section>
<div class="container-xl py-5">
    <div class="row g-4">
        @forelse ($packages as $package)
            <div class="col-md-6 col-lg-4">@include('packages._card')</div>
        @empty
            <p class="text-muted">No packages found. Try another park or send a custom enquiry from any park page.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $packages->links() }}</div>
</div>
@endsection
