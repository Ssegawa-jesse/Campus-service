@extends('layouts.app')
@section('title', 'Fines')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">Fines</h3>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card card-body"><small class="text-muted">Unpaid</small><div class="fs-4 fw-bold text-danger">UGX {{ number_format($totals['unpaid']) }}</div></div></div>
    <div class="col-md-4"><div class="card card-body"><small class="text-muted">Collected</small><div class="fs-4 fw-bold text-success">UGX {{ number_format($totals['paid']) }}</div></div></div>
    <div class="col-md-4"><div class="card card-body"><small class="text-muted">Waived</small><div class="fs-4 fw-bold">UGX {{ number_format($totals['waived']) }}</div></div></div>
</div>
@php($status = request('status', 'unpaid'))
<div class="d-flex flex-wrap gap-2 mb-3">
    <ul class="nav nav-pills">
        @foreach(['unpaid' => 'Unpaid', 'paid' => 'Paid', 'waived' => 'Waived', 'all' => 'All'] as $key => $label)
            <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.fines.index', ['status' => $key]) }}">{{ $label }}</a></li>
        @endforeach
    </ul>
    <form class="ms-auto"><input type="hidden" name="status" value="{{ $status }}"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Member name or number"></form>
</div>
<div class="card shadow-sm">
    <table class="table mb-0">
        <thead><tr><th>Member</th><th>Book</th><th>Amount</th><th>Status</th><th style="width:35%"></th></tr></thead>
        <tbody>
        @forelse($fines as $f)
            <tr>
                <td><a href="{{ route('admin.members.show', $f->member) }}">{{ $f->member->user->name }}</a><br><small class="text-muted">{{ $f->member->member_no }}</small></td>
                <td>{{ $f->loan?->copy->book->title ?? '—' }}@if($f->loan && ! $f->loan->returned_at)<br><small class="text-danger">Not yet returned — still growing</small>@endif</td>
                <td>UGX {{ number_format($f->amount) }}</td>
                <td>{{ ucfirst($f->status) }}
                    @if($f->status === 'waived')<br><small class="text-muted">by {{ $f->waiver?->name }}: {{ $f->reason }}</small>@endif
                    @if($f->status === 'paid' && $p = $f->payments->firstWhere('status', 'successful'))<br><small class="text-muted">{{ $p->methodLabel() }} · {{ $p->reference }}</small>@endif
                </td>
                <td>
                    @if($f->status === 'unpaid')
                        <div class="d-flex gap-1">
                            <form method="POST" action="{{ route('admin.fines.cash', $f) }}" onsubmit="return confirm('Record cash payment of UGX {{ number_format($f->amount) }}?')">@csrf<button class="btn btn-sm btn-outline-success">Cash paid</button></form>
                            <form method="POST" action="{{ route('admin.fines.waive', $f) }}" class="d-flex gap-1 flex-grow-1">@csrf
                                <input name="reason" class="form-control form-control-sm" placeholder="Reason for waiver" required>
                                <button class="btn btn-sm btn-outline-secondary">Waive</button></form>
                        </div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted text-center py-4">No fines.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $fines->links('pagination::bootstrap-5') }}</div>
@endsection
