@extends('layouts.app')
@section('title', 'Operator dashboard')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>{{ $operator->company_name }}</h1>
    <a href="{{ route('operator.packages.create') }}" class="btn btn-gold"><i class="bi bi-plus-lg"></i> New package</a>
</div>

@if ($operator->status === 'pending')
    <div class="alert alert-warning d-flex gap-3 align-items-start">
        <i class="bi bi-hourglass-split fs-4"></i>
        <div><strong>Awaiting approval.</strong> Your registration was sent to the UWA administrators on {{ $operator->created_at->format('d M Y') }}. You'll receive an SMS and email when you're approved, and then your packages become visible to tourists.
        Make sure your <a href="{{ route('operator.profile.edit') }}">company profile</a> has your licence number, contacts and the parks you serve – it speeds up approval.</div>
    </div>
@elseif ($operator->status === 'rejected')
    <div class="alert alert-danger d-flex gap-3 align-items-start">
        <i class="bi bi-x-octagon fs-4"></i>
        <div><strong>Your application was not approved.</strong> @if ($operator->rejection_reason) Reason: {{ $operator->rejection_reason }}.@endif
        Please update your <a href="{{ route('operator.profile.edit') }}">company profile</a> and <a href="{{ route('contact') }}">contact us</a> to be reviewed again.</div>
    </div>
@else
    <div class="alert alert-success py-2 small"><i class="bi bi-patch-check-fill"></i> Approved operator{{ $operator->approved_at ? ' since '.$operator->approved_at->format('d M Y') : '' }}. Your packages are visible to tourists.</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="kpi"><i class="bi bi-inbox"></i><div class="value">{{ $stats['pending'] }}</div><div class="label">New enquiries</div></div></div>
    <div class="col-6 col-md-3"><div class="kpi"><i class="bi bi-check2-circle"></i><div class="value">{{ $stats['confirmed'] }}</div><div class="label">Paid / confirmed</div></div></div>
    <div class="col-6 col-md-3"><div class="kpi"><i class="bi bi-cash-coin"></i><div class="value fs-5">{{ ugx($stats['revenue']) }}</div><div class="label">Revenue</div></div></div>
    <div class="col-6 col-md-3"><div class="kpi"><i class="bi bi-eye"></i><div class="value">{{ $stats['package_views'] }}</div><div class="label">Package views</div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h6>Enquiries & revenue – last 6 months</h6>
            <canvas id="opChart" height="200"></canvas>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="d-flex justify-content-between"><h6>Latest enquiries</h6><a href="{{ route('operator.bookings.index') }}" class="small">All</a></div>
            @forelse ($recent as $booking)
                <a href="{{ route('operator.bookings.show', $booking) }}" class="d-flex justify-content-between border-bottom py-2 text-reset small">
                    <span><strong>{{ $booking->tourist->name }}</strong><br><span class="text-muted">{{ $booking->park->name }} · {{ $booking->travel_date->format('d M') }}</span></span>
                    @include('partials.booking-status')
                </a>
            @empty
                <p class="text-muted small">No enquiries yet.</p>
            @endforelse
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
const m = @json($monthly);
new Chart(document.getElementById('opChart'), {
    data: { labels: m.map(x => x.label), datasets: [
        { type: 'bar', label: 'Enquiries', data: m.map(x => x.enquiries), backgroundColor: '#1f4d3a', yAxisID: 'y' },
        { type: 'line', label: 'Revenue (UGX)', data: m.map(x => x.revenue), borderColor: '#e0a526', backgroundColor: '#e0a526', tension: .3, yAxisID: 'y1' },
    ] },
    options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } } } }
});
</script>
@endpush
