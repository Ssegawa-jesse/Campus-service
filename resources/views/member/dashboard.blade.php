@extends('layouts.app')
@section('title', 'My library')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Hello, {{ auth()->user()->name }}</h3>
        <span class="text-muted">Library number <strong>{{ $member->member_no }}</strong> · {{ ucfirst($member->type) }}@if($member->expiry_date) · valid until {{ $member->expiry_date->format('d M Y') }}@endif</span>
    </div>
    <a href="{{ route('my.card') }}" class="btn btn-outline-primary"><i class="bi bi-qr-code"></i> My library card</a>
</div>

@if($blocks)
    <div class="alert alert-warning"><strong>You cannot borrow right now:</strong>
        <ul class="mb-0">@foreach($blocks as $b)<li>{{ $b }}</li>@endforeach</ul></div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat-card"><div class="card-body">
        <div class="text-muted small">Books on loan</div>
        <div class="display-6">{{ $loans->count() }} <small class="fs-6 text-muted">/ {{ $member->loanLimit() }}</small></div>
    </div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body">
        <div class="text-muted small">Active reservations</div>
        <div class="display-6">{{ $reservations->count() }}</div>
    </div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body">
        <div class="text-muted small">Unpaid fines</div>
        <div class="display-6 {{ $owed ? 'text-danger' : '' }}">UGX {{ number_format($owed) }}</div>
        @if($owed)<a href="{{ route('my.fines') }}" class="small">Pay with Mobile Money &rarr;</a>@endif
    </div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between"><strong>Books I have</strong><a href="{{ route('my.loans') }}" class="small">History</a></div>
            <ul class="list-group list-group-flush">
                @forelse($loans as $loan)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('catalog.show', $loan->copy->book) }}">{{ $loan->copy->book->title }}</a><br>
                            <small class="{{ $loan->isOverdue() ? 'text-danger fw-semibold' : 'text-muted' }}">
                                Due {{ $loan->due_at->format('D d M Y') }}
                                @if($loan->isOverdue()) — {{ $loan->daysOverdue() }} days overdue (fine UGX {{ number_format($loan->calculateFine()) }}) @endif
                            </small>
                        </div>
                        @unless($loan->isOverdue())
                            <form method="POST" action="{{ route('my.loans.renew', $loan) }}">@csrf<button class="btn btn-sm btn-outline-primary">Renew</button></form>
                        @endunless
                    </li>
                @empty
                    <li class="list-group-item text-muted">You have no books on loan. <a href="{{ route('catalog.index') }}">Search the catalogue</a>.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><strong>My reservations</strong></div>
            <ul class="list-group list-group-flush">
                @forelse($reservations as $r)
                    <li class="list-group-item">
                        {{ $r->book->title }}<br>
                        @if($r->status === 'ready')
                            <span class="badge bg-success">Ready — collect by {{ $r->expires_at->format('d M Y') }}</span>
                        @else
                            <span class="badge bg-secondary">Waiting · #{{ $r->position() }} in line</span>
                        @endif
                    </li>
                @empty
                    <li class="list-group-item text-muted">No active reservations.</li>
                @endforelse
            </ul>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between"><strong>My recommendations</strong><a href="{{ route('suggestions.create') }}" class="small">+ New</a></div>
            <ul class="list-group list-group-flush">
                @forelse($suggestions as $s)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $s->title }}</span><span class="badge bg-{{ $s->statusColour() }} align-self-center">{{ $s->statusLabel() }}</span></li>
                @empty
                    <li class="list-group-item text-muted">Can't find a book? <a href="{{ route('suggestions.create') }}">Recommend it</a>.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
