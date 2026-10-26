@extends('layouts.app')
@section('title', 'Dashboard')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">Library dashboard</h3>
<div class="row g-3 mb-4">
    @foreach([
        ['Titles', $stats['titles'], 'book', route('admin.books.index')],
        ['Copies', $stats['copies'], 'stack', route('admin.books.index')],
        ['On loan', $stats['on_loan'], 'arrow-left-right', route('admin.loans.index')],
        ['Overdue', $stats['overdue'], 'exclamation-triangle', route('admin.loans.index', ['status' => 'overdue'])],
        ['Members', $stats['members'], 'people', route('admin.members.index')],
        ['Open reservations', $stats['reservations'], 'bookmark-star', route('admin.reservations.index')],
        ['Unpaid fines (UGX)', number_format($stats['unpaid_fines']), 'cash-coin', route('admin.fines.index')],
        ['New recommendations', $stats['suggestions'], 'lightbulb', route('admin.suggestions.index')],
    ] as [$label, $value, $icon, $url])
        <div class="col-6 col-md-3">
            <a href="{{ $url }}" class="text-decoration-none text-reset">
                <div class="card stat-card h-100 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between"><span class="text-muted small">{{ $label }}</span><i class="bi bi-{{ $icon }} text-primary"></i></div>
                    <div class="fs-3 fw-bold">{{ $value }}</div>
                </div></div>
            </a>
        </div>
    @endforeach
</div>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong class="text-danger">Overdue loans</strong></div>
            <table class="table table-sm mb-0">
                <thead><tr><th>Member</th><th>Book</th><th>Due</th></tr></thead>
                <tbody>
                @forelse($overdueLoans as $l)
                    <tr><td><a href="{{ route('admin.members.show', $l->member) }}">{{ $l->member->user->name }}</a><br><small class="text-muted">{{ $l->member->user->phone }}</small></td>
                        <td>{{ $l->copy->book->title }}</td><td class="text-danger">{{ $l->due_at->format('d M') }} ({{ $l->daysOverdue() }}d)</td></tr>
                @empty
                    <tr><td colspan="3" class="text-muted text-center py-3">No overdue loans.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between"><strong>Recent loans</strong><a class="small" href="{{ route('admin.circulation.index') }}">Issue / return</a></div>
            <table class="table table-sm mb-0">
                <thead><tr><th>Member</th><th>Book</th><th>Issued</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($recentLoans as $l)
                    <tr><td>{{ $l->member->user->name }}</td><td>{{ $l->copy->book->title }}</td><td>{{ $l->issued_at->format('d M H:i') }}</td>
                        <td>{!! $l->returned_at ? '<span class="badge bg-success">Returned</span>' : ($l->isOverdue() ? '<span class="badge bg-danger">Overdue</span>' : '<span class="badge bg-warning text-dark">On loan</span>') !!}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center py-3">No loans yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
