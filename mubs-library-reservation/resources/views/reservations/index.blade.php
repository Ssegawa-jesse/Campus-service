@extends('layouts.app')

@section('title', 'Reservations')

@section('content')
<!-- Start: Main content -->
<main class="page-main">
    <div class="page-banner bg-warm-mesh">
        <div class="container page-banner-inner">
            <div>
                <p class="kicker">Library desk</p>
                <h1>Reservations already made</h1>
                <p>Every submitted reservation appears here after it is saved to the database.</p>
            </div>
            <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-sm glow-orange">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Reserve a book
            </a>
        </div>
    </div>

    <div class="container list-body">

        <!-- Start: Success flash -->
        @if (session('success'))
            <div class="flash" role="status">
                {{ session('success') }}
            </div>
        @endif
        <!-- End: Success flash -->

        <!-- Start: Reservations table -->
        <div class="table-card">
            <div class="table-wrap">
                <table class="reservations">
                    <thead>
                        <tr>
                            <th class="accent">Student</th>
                            <th>Reg. number</th>
                            <th>Book</th>
                            <th>Pickup date</th>
                            <th>Programme</th>
                            <th class="right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservations as $reservation)
                            <tr>
                                <td class="strong">{{ $reservation->student_name }}</td>
                                <td class="muted">{{ $reservation->registration_number }}</td>
                                <td>{{ $reservation->book_title }}</td>
                                <td class="date">{{ $reservation->pickup_date->format('d M Y') }}</td>
                                <td class="muted">{{ $reservation->programme }}</td>
                                <td class="right">
                                    <div class="row-actions">
                                        <a href="{{ route('reservations.edit', $reservation) }}" class="btn btn-edit btn-xs">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                            Edit
                                        </a>
                                        <form action="{{ route('reservations.destroy', $reservation) }}" method="POST"
                                              onsubmit="return confirm('Delete this reservation?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-delete btn-xs">
                                                <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="6">
                                    No reservations yet. <a href="{{ route('reservations.create') }}">Reserve the first book</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-note">
                Showing {{ $reservations->count() }} {{ Str::plural('reservation', $reservations->count()) }}
                from the <code>reservations</code> table.
            </div>
        </div>
        <!-- End: Reservations table -->

    </div>
</main>
<!-- End: Main content -->
@endsection
