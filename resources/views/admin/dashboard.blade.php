@extends('layouts.app')
@section('title', 'Dashboard')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<section class="sl-hero p-4 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <div class="me-auto">
            <div class="opacity-75 small">{{ now()->format('l, d F Y') }}</div>
            <h3 class="mb-1">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }} 👋</h3>
            <div class="opacity-75">{{ $stats['overdue'] }} overdue {{ Str::plural('loan', $stats['overdue']) }} · {{ $stats['reservations'] }} open {{ Str::plural('reservation', $stats['reservations']) }} · {{ $stats['suggestions'] }} new {{ Str::plural('recommendation', $stats['suggestions']) }}</div>
        </div>
        <a href="{{ route('admin.circulation.index') }}" class="btn btn-warning"><i class="bi bi-qr-code-scan"></i> Issue / return</a>
        <a href="{{ route('admin.books.create') }}" class="btn btn-light-glass"><i class="bi bi-plus-lg"></i> Add book</a>
        <a href="{{ route('admin.members.create') }}" class="btn btn-light-glass"><i class="bi bi-person-plus"></i> Add member</a>
    </div>
</section>

<div class="row g-3 mb-4">
    @foreach([
        ['Book titles', $stats['titles'], 'book', 'tone-indigo', route('admin.books.index')],
        ['Copies', $stats['copies'], 'stack', 'tone-violet', route('admin.books.index')],
        ['On loan', $stats['on_loan'], 'arrow-left-right', 'tone-sky', route('admin.loans.index')],
        ['Overdue', $stats['overdue'], 'exclamation-triangle', 'tone-rose', route('admin.loans.index', ['status' => 'overdue'])],
        ['Members', $stats['members'], 'people', 'tone-pink', route('admin.members.index')],
        ['Open reservations', $stats['reservations'], 'bookmark-star', 'tone-teal', route('admin.reservations.index')],
        ['Unpaid fines (UGX)', number_format($stats['unpaid_fines']), 'cash-coin', 'tone-amber', route('admin.fines.index')],
        ['New recommendations', $stats['suggestions'], 'lightbulb', 'tone-emerald', route('admin.suggestions.index')],
    ] as [$label, $value, $icon, $tone, $url])
        <div class="col-6 col-md-3">
            <a href="{{ $url }}" class="text-reset"><div class="card stat-tile">
                <div class="icon {{ $tone }}"><i class="bi bi-{{ $icon }}"></i></div>
                <div class="min-w-0"><div class="value">{{ $value }}</div><div class="label text-truncate">{{ $label }}</div></div>
            </div></a>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-graph-up-arrow text-primary"></i> Circulation — last 14 days</span><a href="{{ route('admin.reports.index') }}" class="small fw-normal">Reports</a></div>
            <div class="card-body"><canvas id="activity" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart text-primary"></i> Copies by status</div>
            <div class="card-body d-flex align-items-center"><canvas id="status" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-trophy text-warning"></i> Most borrowed</div>
            <ul class="list-group list-group-flush">
                @foreach($topBooks as $i => $book)
                    <li class="list-group-item d-flex align-items-center gap-3">
                        <span class="fw-bold text-muted" style="width:1rem">{{ $i + 1 }}</span>
                        <div class="thumb">@include('partials.cover', ['book' => $book])</div>
                        <div class="flex-grow-1 min-w-0"><a href="{{ route('catalog.show', $book) }}" class="fw-medium text-reset d-block text-truncate">{{ $book->title }}</a><small class="text-muted">{{ $book->loans_count }} {{ Str::plural('loan', $book->loans_count) }}</small></div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span class="text-danger"><i class="bi bi-alarm"></i> Overdue</span><a href="{{ route('admin.loans.index', ['status' => 'overdue']) }}" class="small fw-normal">All</a></div>
            <ul class="list-group list-group-flush">
                @forelse($overdueLoans as $l)
                    <li class="list-group-item d-flex align-items-center gap-3">
                        @include('partials.avatar', ['user' => $l->member->user, 'size' => 38])
                        <div class="flex-grow-1 min-w-0">
                            <a href="{{ route('admin.members.show', $l->member) }}" class="fw-medium text-reset">{{ $l->member->user->name }}</a>
                            <div class="small text-muted text-truncate">{{ $l->copy->book->title }}</div>
                        </div>
                        <span class="badge soft-rose">{{ $l->daysOverdue() }}d late</span>
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted py-4"><i class="bi bi-emoji-smile fs-3 d-block"></i>No overdue books</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-xl-4 col-lg-12">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-clock-history text-primary"></i> Recent loans</span><a href="{{ route('admin.loans.index') }}" class="small fw-normal">All</a></div>
            <ul class="list-group list-group-flush">
                @forelse($recentLoans as $l)
                    <li class="list-group-item d-flex align-items-center gap-3">
                        <div class="thumb">@include('partials.cover', ['book' => $l->copy->book])</div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-medium text-truncate">{{ $l->copy->book->title }}</div>
                            <div class="small text-muted">{{ $l->member->user->name }} · {{ $l->issued_at->diffForHumans() }}</div>
                        </div>
                        @if($l->returned_at)<span class="badge soft-teal">Returned</span>@elseif($l->isOverdue())<span class="badge soft-rose">Overdue</span>@else<span class="badge soft-amber">On loan</span>@endif
                    </li>
                @empty
                    <li class="list-group-item text-muted">No loans yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-person-plus text-primary"></i> Newest members</span><a href="{{ route('admin.members.index') }}" class="small fw-normal">All members</a></div>
            <div class="card-body d-flex flex-wrap gap-4">
                @foreach($newMembers as $m)
                    <a href="{{ route('admin.members.show', $m) }}" class="d-flex align-items-center gap-2 text-reset">
                        @include('partials.avatar', ['user' => $m->user, 'size' => 44])
                        <div class="lh-sm"><div class="fw-medium">{{ $m->user->name }}</div><small class="text-muted">{{ $m->programme ?: ucfirst($m->type) }}</small></div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
Chart.defaults.font.family = 'Poppins, system-ui, sans-serif';
Chart.defaults.color = '#6b7280';
const ctx = document.getElementById('activity').getContext('2d');
const grad = ctx.createLinearGradient(0, 0, 0, 260);
grad.addColorStop(0, 'rgba(124, 58, 237, .35)');
grad.addColorStop(1, 'rgba(124, 58, 237, 0)');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($activity['labels']),
        datasets: [
            {label: 'Issued', data: @json($activity['issued']), borderColor: '#7c3aed', backgroundColor: grad, fill: true, cubicInterpolationMode: 'monotone', pointRadius: 3},
            {label: 'Returned', data: @json($activity['returned']), borderColor: '#f59e0b', backgroundColor: '#f59e0b', cubicInterpolationMode: 'monotone', pointRadius: 3}
        ]
    },
    options: {plugins: {legend: {position: 'bottom', labels: {usePointStyle: true}}}, scales: {y: {beginAtZero: true, ticks: {precision: 0}, grid: {color: '#eef0f6'}}, x: {grid: {display: false}}}}
});
const status = @json($copyStatus);
const names = {available: 'Available', on_loan: 'On loan', on_hold: 'On hold', lost: 'Lost', damaged: 'Damaged'};
const colours = {available: '#10b981', on_loan: '#7c3aed', on_hold: '#0ea5e9', lost: '#6b7280', damaged: '#e11d48'};
new Chart(document.getElementById('status'), {
    type: 'doughnut',
    data: {labels: Object.keys(status).map(k => names[k] ?? k), datasets: [{data: Object.values(status), backgroundColor: Object.keys(status).map(k => colours[k] ?? '#999'), borderWidth: 0}]},
    options: {cutout: '68%', plugins: {legend: {position: 'bottom', labels: {usePointStyle: true}}}}
});
</script>
@endpush
