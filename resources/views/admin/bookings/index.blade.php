@extends('layouts.app')
@section('title', 'All bookings')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>All bookings</h1>
    <form><select name="status" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">All statuses</option>@foreach (\App\Models\Booking::STATUSES as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></form>
</div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table align-middle mb-0 small">
        <thead><tr><th>Ref</th><th>Tourist</th><th>Operator</th><th>Park</th><th>Travel</th><th>Pax</th><th>Amount</th><th>Status</th><th>Created</th></tr></thead>
        <tbody>
            @forelse ($bookings as $b)
                <tr>
                    <td>{{ $b->reference }}</td><td>{{ $b->tourist->name }}<br><span class="text-muted">{{ $b->contact_phone }}</span></td>
                    <td>{{ $b->operator->company_name }}</td><td>{{ $b->park->name }}</td>
                    <td>{{ $b->travel_date->format('d M Y') }}</td><td>{{ $b->group_size }}</td>
                    <td>{{ $b->quoted_amount ? ugx($b->quoted_amount) : '–' }}</td>
                    <td>@include('partials.booking-status', ['booking' => $b])</td>
                    <td>{{ $b->created_at->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No bookings.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $bookings->links() }}</div>
@endsection
