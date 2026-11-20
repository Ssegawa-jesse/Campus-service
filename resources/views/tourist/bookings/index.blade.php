@extends('layouts.app')
@section('title', 'My bookings')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>My bookings</h1><a href="{{ route('tourist.bookings.create') }}" class="btn btn-gold"><i class="bi bi-plus-lg"></i> New enquiry</a></div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Ref</th><th>Park</th><th>Operator</th><th>Travel date</th><th>Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td class="small">{{ $booking->reference }}</td>
                        <td>{{ $booking->park->name }}<div class="small text-muted">{{ $booking->package?->title }}</div></td>
                        <td class="small">{{ $booking->operator->company_name }}</td>
                        <td class="small">{{ $booking->travel_date->format('d M Y') }}</td>
                        <td class="small">{{ $booking->quoted_amount ? ugx($booking->quoted_amount) : '–' }}</td>
                        <td>@include('partials.booking-status')</td>
                        <td class="text-end">
                            @if ($booking->canBePaid())<a href="{{ route('payments.checkout', $booking) }}" class="btn btn-sm btn-gold">Pay</a>@endif
                            <a href="{{ route('tourist.bookings.show', $booking) }}" class="btn btn-sm btn-outline-forest">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No bookings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $bookings->links() }}</div>
@endsection
