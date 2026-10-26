@extends('layouts.app')
@section('title', 'Library card')
@section('content')
<div class="d-flex justify-content-center">
    <div class="card shadow" style="width: 380px; border-radius: 1rem; overflow: hidden">
        <div class="bg-lib text-white p-3">
            <div class="fw-bold"><i class="bi bi-book-half"></i> {{ \App\Support\LibrarySettings::get('library_name') }}</div>
            <small>Library membership card</small>
        </div>
        <div class="card-body d-flex gap-3 align-items-center">
            <div>{!! \App\Support\Qr::svg($member->member_no, 130) !!}</div>
            <div>
                <div class="fw-bold">{{ $member->user->name }}</div>
                <div class="small">{{ ucfirst($member->type) }}@if($member->programme)<br>{{ $member->programme }}@endif</div>
                <div class="mt-2"><code class="fs-6">{{ $member->member_no }}</code></div>
                @if($member->expiry_date)<div class="small text-muted">Valid until {{ $member->expiry_date->format('d M Y') }}</div>@endif
            </div>
        </div>
    </div>
</div>
<p class="text-center mt-3 no-print">
    Show this QR code at the counter to borrow books.
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="bi bi-printer"></i> Print card</button>
</p>
@endsection
