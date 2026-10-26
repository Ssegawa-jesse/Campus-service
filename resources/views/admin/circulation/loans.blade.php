@extends('layouts.app')
@section('title', 'Loans')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">Loans</h3>
@php($status = request('status', 'active'))
<ul class="nav nav-pills mb-3">
    @foreach(['active' => 'On loan', 'overdue' => 'Overdue', 'returned' => 'Returned', 'all' => 'All'] as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.loans.index', ['status' => $key]) }}">{{ $label }}</a></li>
    @endforeach
</ul>
<form class="mb-3"><input type="hidden" name="status" value="{{ $status }}"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Member, number, title or copy code"></form>
<div class="card shadow-sm">
    <table class="table table-hover mb-0">
        <thead><tr><th>Member</th><th>Book</th><th>Issued</th><th>Due</th><th>Returned</th><th>Fine</th><th></th></tr></thead>
        <tbody>
        @forelse($loans as $l)
            <tr class="{{ $l->isOverdue() ? 'table-danger' : '' }}">
                <td><a href="{{ route('admin.members.show', $l->member) }}">{{ $l->member->user->name }}</a><br><small class="text-muted">{{ $l->member->member_no }}</small></td>
                <td>{{ $l->copy->book->title }}<br><small class="text-muted">{{ $l->copy->code }}</small></td>
                <td>{{ $l->issued_at->format('d M Y') }}</td>
                <td>{{ $l->due_at->format('d M Y') }}@if($l->isOverdue())<br><small class="text-danger">{{ $l->daysOverdue() }} days late</small>@endif</td>
                <td>{{ $l->returned_at?->format('d M Y') ?? '—' }}</td>
                <td>@if($l->fine) UGX {{ number_format($l->fine->amount) }} <small>({{ $l->fine->status }})</small> @elseif($l->isOverdue()) UGX {{ number_format($l->calculateFine()) }} @else — @endif</td>
                <td class="text-end">@if(! $l->returned_at && ! $l->isOverdue())<form method="POST" action="{{ route('admin.loans.renew', $l) }}">@csrf<button class="btn btn-sm btn-outline-primary">Renew</button></form>@endif</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted text-center py-4">No loans.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $loans->links('pagination::bootstrap-5') }}</div>
@endsection
