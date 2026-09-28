@extends('layouts.app')
@section('title', 'Booking enquiries')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>Booking enquiries</h1>
    <form><select name="status" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">All statuses</option>@foreach (\App\Models\Booking::STATUSES as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></form>
</div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Ref</th><th>Tourist</th><th>Park / package</th><th>Date</th><th>Pax</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
            @forelse ($bookings as $booking)
                <tr>
                    <td class="small">{{ $booking->reference }}</td>
                    <td>{{ $booking->tourist->name }}<div class="small text-muted">{{ $booking->contact_phone }}</div></td>
                    <td class="small">{{ $booking->park->name }}<div class="text-muted">{{ $booking->package?->title }}</div></td>
                    <td class="small">{{ $booking->travel_date->format('d M Y') }}</td>
                    <td>{{ $booking->group_size }}</td>
                    <td class="small">{{ $booking->quoted_amount ? ugx($booking->quoted_amount) : '–' }}</td>
                    <td>@include('partials.booking-status')</td>
                    <td><a href="{{ route('operator.bookings.show', $booking) }}" class="btn btn-sm btn-forest">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No enquiries.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $bookings->links() }}</div>
@endsection
