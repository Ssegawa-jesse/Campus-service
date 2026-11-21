@extends('layouts.app')
@section('title', 'Smart itineraries')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1><i class="bi bi-calendar2-week"></i> Smart itineraries</h1></div>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Reference</th><th>Tourist</th><th>Start</th><th>Days</th><th>Category</th><th>Total</th><th>Status</th></tr></thead>
    @forelse ($itineraries as $it)
        <tr><td><a href="{{ route('itinerary.show', $it) }}">{{ $it->reference }}</a><div class="small text-muted">{{ $it->title }}</div></td><td>{{ $it->user?->name }}</td><td>{{ $it->start_date->format('d M Y') }}</td><td>{{ $it->days }}</td>
            <td class="small">{{ config('murchison.categories')[$it->visitor_category]['label'] ?? $it->visitor_category }}</td><td class="text-nowrap">{{ ugx($it->total_ugx) }}</td>
            <td><span class="badge bg-{{ $it->bookings_count ? 'success' : 'secondary' }}">{{ $it->bookings_count ? 'booked' : $it->status }}</span></td></tr>
    @empty<tr><td colspan="7" class="text-muted">None yet.</td></tr>@endforelse
</table></div></div>
<div class="mt-2">{{ $itineraries->links() }}</div>
@endsection
