@extends('layouts.app')
@section('title', $member->user->name)
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h3 class="mb-0">{{ $member->user->name }} <span class="badge bg-{{ $member->user->isActive() ? 'success' : 'danger' }} fs-6">{{ ucfirst($member->user->status) }}</span></h3>
        <span class="text-muted"><code>{{ $member->member_no }}</code> · {{ ucfirst($member->type) }} · {{ $member->user->email }} · {{ $member->user->phone }}@if($member->programme) · {{ $member->programme }}@endif</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.members.card', $member) }}" class="btn btn-outline-primary"><i class="bi bi-person-vcard"></i> Card</a>
        <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-outline-secondary">Edit</a>
        <form method="POST" action="{{ route('admin.members.status', $member) }}">@csrf
            <button class="btn btn-outline-{{ $member->user->isActive() ? 'danger' : 'success' }}">{{ $member->user->isActive() ? 'Suspend' : 'Re-activate' }}</button></form>
    </div>
</div>
@if($blocks)<div class="alert alert-warning"><strong>Cannot borrow:</strong> {{ implode(' ', $blocks) }}</div>
@else<div class="alert alert-success py-2">Can borrow {{ $member->loanLimit() - $member->activeLoans()->count() }} more {{ Str::plural('book', $member->loanLimit() - $member->activeLoans()->count()) }}.</div>@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong>Loans</strong></div>
            <table class="table table-sm mb-0">
                <thead><tr><th>Book</th><th>Issued</th><th>Due</th><th>Returned</th><th></th></tr></thead>
                <tbody>
                @forelse($loans as $l)
                    <tr class="{{ $l->isOverdue() ? 'table-danger' : '' }}">
                        <td>{{ $l->copy->book->title }}<br><small class="text-muted">{{ $l->copy->code }}</small></td>
                        <td>{{ $l->issued_at->format('d M Y') }}</td><td>{{ $l->due_at->format('d M Y') }}</td>
                        <td>{{ $l->returned_at?->format('d M Y') ?? '—' }}</td>
                        <td>@if(! $l->returned_at && ! $l->isOverdue())<form method="POST" action="{{ route('admin.loans.renew', $l) }}">@csrf<button class="btn btn-sm btn-outline-primary">Renew</button></form>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center py-3">No loans.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $loans->links('pagination::bootstrap-5') }}</div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white"><strong>Fines</strong> (unpaid UGX {{ number_format($member->outstandingFines()) }})</div>
            <ul class="list-group list-group-flush">
                @forelse($fines as $f)
                    <li class="list-group-item small d-flex justify-content-between"><span>{{ $f->loan?->copy->book->title ?? 'Charge' }}</span><span>UGX {{ number_format($f->amount) }} · {{ $f->status }}</span></li>
                @empty
                    <li class="list-group-item text-muted small">No fines.</li>
                @endforelse
            </ul>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong>Open reservations</strong></div>
            <ul class="list-group list-group-flush">
                @forelse($reservations as $r)
                    <li class="list-group-item small">{{ $r->book->title }} — {{ $r->status === 'ready' ? 'ready until '.$r->expires_at->format('d M') : 'waiting #'.$r->position() }}</li>
                @empty
                    <li class="list-group-item text-muted small">None.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
