@extends('layouts.app')
@section('title', 'Admin dashboard')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>Dashboard & analytics</h1>
    <a href="{{ route('admin.reports.export') }}" class="btn btn-outline-forest"><i class="bi bi-download"></i> Export CSV report</a>
</div>

<div class="card border-0 shadow-sm mb-4 {{ $pendingOperators->isNotEmpty() ? 'border-start border-4 border-warning' : '' }}">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><i class="bi bi-person-check text-gold me-1"></i> Operators awaiting approval
                <span class="badge {{ $pendingOperators->isNotEmpty() ? 'bg-danger' : 'bg-secondary' }} ms-1">{{ $pendingOperators->count() }}</span></h5>
            <a href="{{ route('admin.operators.index') }}" class="small">All operators</a>
        </div>
        @forelse ($pendingOperators as $o)
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 border-top py-2">
                <div style="flex:1 1 320px;min-width:0">
                    <strong>{{ $o->company_name }}</strong> <span class="small text-muted">· {{ $o->user->name }} · {{ $o->contact_phone }} · {{ $o->contact_email }}</span>
                    <div class="small text-muted">Registered {{ $o->created_at->diffForHumans() }}{{ $o->licence_no ? ' · Licence '.$o->licence_no : ' · no licence number given' }}</div>
                </div>
                <div style="min-width:260px">@include('admin.operators._actions')</div>
            </div>
        @empty
            <p class="text-muted small mb-0">No operators are waiting. New operator sign-ups appear here immediately, and every admin gets an SMS and email.</p>
        @endforelse
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach ([['tourists', 'Tourists', 'people'], ['operators', 'Operators', 'briefcase'], ['admins', 'Administrators', 'shield-lock'], ['views', 'Page & 360° views', 'eye'], ['plans', 'Trip plans', 'magic'], ['bookings', 'Bookings', 'calendar-check'], ['scenes', '360° scenes', 'badge-vr'], ['parks', 'Parks', 'tree']] as [$k, $l, $i])
        <div class="col-6 col-md-3"><div class="kpi"><i class="bi bi-{{ $i }}"></i><div class="value">{{ number_format($stats[$k]) }}</div><div class="label">{{ $l }}</div></div></div>
    @endforeach
    <div class="col-6 col-md-3"><div class="kpi"><i class="bi bi-cash-coin"></i><div class="value fs-5">{{ ugx($stats['revenue']) }}</div><div class="label">Payments received</div></div></div>
</div>

<div class="row g-4">
    <div class="col-12"><div class="card border-0 shadow-sm"><div class="card-body">
        <h6>Trends – last 12 months</h6><canvas id="trendChart" height="90"></canvas>
    </div></div></div>
    <div class="col-lg-7"><div class="card border-0 shadow-sm h-100"><div class="card-body">
        <h6>Most viewed parks</h6><canvas id="viewsChart" height="220"></canvas>
    </div></div></div>
    <div class="col-lg-5"><div class="card border-0 shadow-sm h-100"><div class="card-body">
        <h6>Popular interests (trip planner)</h6><canvas id="interestChart" height="220"></canvas>
    </div></div></div>
    <div class="col-lg-7"><div class="card border-0 shadow-sm h-100"><div class="card-body">
        <h6>Bookings per park</h6><canvas id="bookingsChart" height="220"></canvas>
    </div></div></div>
    <div class="col-lg-5"><div class="card border-0 shadow-sm h-100"><div class="card-body">
        <h6>Booking status</h6><canvas id="statusChart" height="220"></canvas>
    </div></div></div>
    <div class="col-12"><div class="card border-0 shadow-sm"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Newest registrations</h6>
            <span class="small text-muted">Logins today: <strong>{{ $loginsToday }}</strong> · failed: <strong class="{{ $failedToday ? 'text-danger' : '' }}">{{ $failedToday }}</strong> · <a href="{{ route('admin.users.activity') }}">Login activity</a></span>
        </div>
        <div class="table-responsive"><table class="table table-sm small align-middle mb-0 mt-2">
            <thead><tr><th>Name</th><th>Email / phone</th><th>Role</th><th>Registered</th><th>Last login</th></tr></thead>
            @foreach ($recentUsers as $u)
                <tr>
                    <td>{{ $u->name }}@if ($u->operator)<div class="text-muted">{{ $u->operator->company_name }}</div>@endif</td>
                    <td>{{ $u->email }}<br><span class="text-muted">{{ $u->phone }}</span></td>
                    <td><span class="badge role-badge role-{{ $u->role }} text-capitalize">{{ $u->role }}</span>
                        @if ($u->operator)<span class="badge bg-{{ $u->operator->statusColor() }}">{{ $u->operator->statusLabel() }}</span>@endif</td>
                    <td>{{ $u->created_at->diffForHumans() }}</td>
                    <td>{{ $u->last_login_at?->diffForHumans() ?? 'never' }}</td>
                </tr>
            @endforeach
        </table></div>
    </div></div></div>
    <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
        <h6>Top 360° scenes</h6>
        <table class="table table-sm small mb-0">
            @foreach ($topScenes as $s)
                <tr><td>{{ $s->title }}<div class="text-muted">{{ $s->park->name }}</div></td><td class="text-end fw-semibold">{{ $s->views_count }}</td></tr>
            @endforeach
        </table>
    </div></div></div>
    <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
        <div class="d-flex justify-content-between"><h6>Latest bookings</h6><a href="{{ route('admin.bookings.index') }}" class="small">All</a></div>
        <table class="table table-sm small mb-0">
            @foreach ($recentBookings as $booking)
                <tr><td>{{ $booking->reference }}<div class="text-muted">{{ $booking->tourist->name }} → {{ $booking->operator->company_name }}</div></td><td>{{ $booking->park->name }}</td><td>@include('partials.booking-status')</td></tr>
            @endforeach
        </table>
    </div></div></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
const green = '#1f4d3a', gold = '#e0a526', red = '#c8102e';
const palette = [green, gold, red, '#2d6a50', '#7a9e7e', '#b9841a', '#5c3d2e', '#3b7ea1', '#8a8d91', '#d9c38a', '#4f6d7a', '#a44a3f'];
const short = n => n.replace(' National Park', '').replace('Impenetrable', '');
const trend = @json($trend);
new Chart(trendChart, { data: { labels: trend.map(t => t.label), datasets: [
    { type: 'line', label: 'Views', data: trend.map(t => t.views), borderColor: green, backgroundColor: green, tension: .3, yAxisID: 'y' },
    { type: 'bar', label: 'Bookings', data: trend.map(t => t.bookings), backgroundColor: gold, yAxisID: 'y' },
    { type: 'line', label: 'Revenue (UGX)', data: trend.map(t => t.revenue), borderColor: red, borderDash: [5, 5], yAxisID: 'y1' },
] }, options: { scales: { y: { beginAtZero: true }, y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } } } } });

const pv = @json($parkViews);
new Chart(viewsChart, { type: 'bar', data: { labels: pv.map(p => short(p.name)), datasets: [{ label: 'Views', data: pv.map(p => p.views_count), backgroundColor: green }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } } } });

const ints = @json($interests);
new Chart(interestChart, { type: 'doughnut', data: { labels: Object.keys(ints), datasets: [{ data: Object.values(ints), backgroundColor: palette }] }, options: { plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } } } });

const bp = @json($bookingsPerPark);
new Chart(bookingsChart, { type: 'bar', data: { labels: bp.map(p => short(p.name)), datasets: [{ label: 'Bookings', data: bp.map(p => p.bookings_count), backgroundColor: gold }] }, options: { plugins: { legend: { display: false } }, scales: { y: { ticks: { precision: 0 } } } } });

const st = @json($statusBreakdown);
new Chart(statusChart, { type: 'pie', data: { labels: Object.keys(st), datasets: [{ data: Object.values(st), backgroundColor: palette }] } });
</script>
@endpush
