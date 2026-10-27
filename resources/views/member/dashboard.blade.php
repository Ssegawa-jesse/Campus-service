@extends('layouts.app')
@section('title', 'My library')
@section('content')
@php $user = auth()->user(); @endphp
<section class="sl-hero p-4 p-md-5 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-4">
        @include('partials.avatar', ['user' => $user, 'size' => 92, 'class' => 'avatar-ring'])
        <div class="me-auto">
            <div class="opacity-75 small">{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }}</div>
            <h2 class="mb-1">{{ $user->name }}</h2>
            <div class="opacity-75">
                {{ $member->programme ?: ucfirst($member->type) }}@if($member->year_of_study) · Year {{ $member->year_of_study }}@endif
                · Library no. <strong>{{ $member->member_no }}</strong>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('my.card') }}" class="btn btn-warning"><i class="bi bi-person-vcard"></i> My library card</a>
            <a href="{{ route('catalog.index') }}" class="btn btn-light-glass"><i class="bi bi-search"></i> Find books</a>
        </div>
    </div>
</section>

@if(! $user->photo)
    <div class="alert alert-info d-flex align-items-center gap-2"><i class="bi bi-camera"></i><div>Add a profile picture so it shows on your library card. <a href="{{ route('profile.edit') }}" class="fw-semibold">Add photo</a></div></div>
@endif
@if($blocks)
    <div class="alert alert-warning"><strong><i class="bi bi-exclamation-triangle"></i> You cannot borrow right now:</strong>
        <ul class="mb-0">@foreach($blocks as $b)<li>{{ $b }}</li>@endforeach</ul></div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card stat-tile"><div class="icon tone-indigo"><i class="bi bi-journal-bookmark"></i></div>
        <div><div class="value">{{ $loans->count() }}<small class="fs-6 text-muted"> / {{ $member->loanLimit() }}</small></div><div class="label">Books with me</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-tile"><div class="icon tone-teal"><i class="bi bi-bookmark-star"></i></div>
        <div><div class="value">{{ $reservations->count() }}</div><div class="label">Reservations</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-tile"><div class="icon tone-violet"><i class="bi bi-book"></i></div>
        <div><div class="value">{{ $borrowedTotal }}</div><div class="label">Books borrowed in total</div></div></div></div>
    <div class="col-6 col-lg-3"><a href="{{ route('my.fines') }}" class="text-reset"><div class="card stat-tile"><div class="icon {{ $owed ? 'tone-rose' : 'tone-emerald' }}"><i class="bi bi-cash-coin"></i></div>
        <div><div class="value {{ $owed ? 'text-danger' : '' }}">UGX {{ number_format($owed) }}</div><div class="label">{{ $owed ? 'Unpaid fines — pay with MoMo' : 'No fines' }}</div></div></div></a></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-journal-bookmark text-primary"></i> Books I have</span><a href="{{ route('my.loans') }}" class="small fw-normal">History</a></div>
            <ul class="list-group list-group-flush">
                @forelse($loans as $loan)
                    @php
                        $total = max(1, $loan->issued_at->diffInDays($loan->due_at));
                        $left = (int) floor(now()->diffInDays($loan->due_at, false));
                        $pct = $loan->isOverdue() ? 100 : min(100, max(4, round((1 - $left / $total) * 100)));
                        $colour = $loan->isOverdue() ? '#e11d48' : ($left <= 2 ? '#f59e0b' : '#10b981');
                    @endphp
                    <li class="list-group-item d-flex align-items-center gap-3 py-3">
                        <div class="thumb" style="width:52px;flex-basis:52px">@include('partials.cover', ['book' => $loan->copy->book])</div>
                        <div class="flex-grow-1 min-w-0">
                            <a href="{{ route('catalog.show', $loan->copy->book) }}" class="fw-medium text-reset d-block text-truncate">{{ $loan->copy->book->title }}</a>
                            <div class="small mb-1" style="color: {{ $colour }}">
                                @if($loan->isOverdue())
                                    <i class="bi bi-alarm"></i> {{ $loan->daysOverdue() }} days overdue — fine UGX {{ number_format($loan->calculateFine()) }}
                                @else
                                    <i class="bi bi-calendar-event"></i> Due {{ $loan->due_at->format('D d M') }} ({{ $left === 0 ? 'today' : $left.' '.Str::plural('day', $left).' left' }})
                                @endif
                            </div>
                            <div class="due-bar"><span style="width: {{ $pct }}%; background: {{ $colour }}"></span></div>
                        </div>
                        @unless($loan->isOverdue())
                            <form method="POST" action="{{ route('my.loans.renew', $loan) }}">@csrf<button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Renew</button></form>
                        @endunless
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted py-5"><i class="bi bi-book fs-2 d-block mb-2"></i>You have no books on loan. <a href="{{ route('catalog.index') }}">Find something to read</a>.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-bookmark-star text-primary"></i> My reservations</div>
            <ul class="list-group list-group-flush">
                @forelse($reservations as $r)
                    <li class="list-group-item d-flex align-items-center gap-3">
                        <div class="thumb">@include('partials.cover', ['book' => $r->book])</div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-medium text-truncate">{{ $r->book->title }}</div>
                            @if($r->status === 'ready')
                                <span class="badge soft-teal"><i class="bi bi-check-circle"></i> Ready — collect by {{ $r->expires_at->format('d M') }}</span>
                            @else
                                <span class="badge soft-indigo">Waiting · #{{ $r->position() }} in line</span>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted small">No active reservations. Reserve books that are on loan from the catalogue.</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-lightbulb text-warning"></i> My recommendations</span><a href="{{ route('suggestions.create') }}" class="small fw-normal">+ New</a></div>
            <ul class="list-group list-group-flush">
                @forelse($suggestions as $s)
                    <li class="list-group-item d-flex justify-content-between align-items-center"><span class="text-truncate">{{ $s->title }}</span><span class="badge bg-{{ $s->statusColour() }}">{{ $s->statusLabel() }}</span></li>
                @empty
                    <li class="list-group-item text-muted small">Can't find a book? <a href="{{ route('suggestions.create') }}">Recommend it</a>.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

@if($forYou->isNotEmpty())
    <h4 class="mb-3"><i class="bi bi-stars text-warning"></i> On the shelf now — you might like</h4>
    <div class="row g-3">
        @foreach($forYou as $book)
            <div class="col-6 col-sm-4 col-md-2">@include('partials.book-card', ['book' => $book])</div>
        @endforeach
    </div>
@endif
@endsection
