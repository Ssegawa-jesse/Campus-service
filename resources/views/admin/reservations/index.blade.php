@extends('layouts.app')
@section('title', 'Reservations')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">Reservations</h3>
@php($status = request('status', 'open'))
<ul class="nav nav-pills mb-3">
    @foreach(['open' => 'Open', 'fulfilled' => 'Fulfilled', 'expired' => 'Expired', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.reservations.index', ['status' => $key]) }}">{{ $label }}</a></li>
    @endforeach
</ul>
<div class="card shadow-sm">
    <table class="table mb-0">
        <thead><tr><th>Book</th><th>Member</th><th>Reserved</th><th>Status</th><th>Held copy</th><th></th></tr></thead>
        <tbody>
        @forelse($reservations as $r)
            <tr class="{{ $r->status === 'ready' ? 'table-success' : '' }}">
                <td>{{ $r->book->title }}</td>
                <td>{{ $r->member->user->name }}<br><small class="text-muted">{{ $r->member->member_no }} · {{ $r->member->user->phone }}</small></td>
                <td>{{ $r->created_at->format('d M Y') }}</td>
                <td>@if($r->status === 'ready') <span class="badge bg-success">Ready until {{ $r->expires_at->format('d M') }}</span>
                    @elseif($r->status === 'waiting') <span class="badge bg-secondary">Waiting #{{ $r->position() }}</span>
                    @else <span class="badge bg-light text-dark border">{{ ucfirst($r->status) }}</span> @endif</td>
                <td>{{ $r->copy?->code ?? '—' }}</td>
                <td class="text-end">@if($r->isOpen())<form method="POST" action="{{ route('admin.reservations.destroy', $r) }}" onsubmit="return confirm('Cancel this reservation?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Cancel</button></form>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted text-center py-4">No reservations.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $reservations->links('pagination::bootstrap-5') }}</div>
@endsection
