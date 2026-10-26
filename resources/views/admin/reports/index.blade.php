@extends('layouts.app')
@section('title', 'Reports')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
    <h3 class="mb-0">Reports &amp; analytics</h3>
    <form class="d-flex gap-2 align-items-end">
        <div><label class="form-label small mb-0">From</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control form-control-sm"></div>
        <div><label class="form-label small mb-0">To</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control form-control-sm"></div>
        <button class="btn btn-sm btn-lib">Apply</button>
    </form>
</div>
<div class="row g-3 mb-4">
    @foreach(['loans' => 'Loans issued', 'returns' => 'Returns', 'overdue' => 'Currently overdue', 'collected' => 'Fines collected (UGX)', 'new_members' => 'New members', 'utilisation' => 'Copies on loan (%)'] as $k => $label)
        <div class="col-6 col-md-2"><div class="card card-body h-100"><small class="text-muted">{{ $label }}</small><div class="fs-4 fw-bold">{{ is_float($summary[$k]) ? $summary[$k] : number_format($summary[$k]) }}</div></div></div>
    @endforeach
</div>
<div class="row g-4 mb-4">
    <div class="col-lg-8"><div class="card shadow-sm"><div class="card-body"><h6>Loans and fines collected — last 6 months</h6><canvas id="trend" height="120"></canvas></div></div></div>
    <div class="col-lg-4"><div class="card shadow-sm"><div class="card-body"><h6>Loans by category</h6><canvas id="cats" height="220"></canvas></div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong>Most borrowed titles</strong></div>
            <table class="table table-sm mb-0">
                @forelse($popular as $row)<tr><td>{{ $row['Title'] }}</td><td class="text-end">{{ $row['Times borrowed'] }}</td></tr>
                @empty<tr><td class="text-muted">No loans in this period.</td></tr>@endforelse
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong>Download reports</strong></div>
            <ul class="list-group list-group-flush">
                @foreach($reports as $key => $label)
                    <li class="list-group-item d-flex justify-content-between align-items-center">{{ $label }}
                        <span>
                            <a href="{{ route('admin.reports.export', [$key, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel (CSV)</a>
                            <a href="{{ route('admin.reports.print', [$key, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF / print</a>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
new Chart(document.getElementById('trend'), {
    data: {
        labels: @json($chart['labels']),
        datasets: [
            {type: 'bar', label: 'Loans', data: @json($chart['loans']), backgroundColor: '#0b3d91', yAxisID: 'y'},
            {type: 'line', label: 'Fines collected (UGX)', data: @json($chart['fines']), borderColor: '#e0a800', backgroundColor: '#e0a800', yAxisID: 'y1'}
        ]
    },
    options: {scales: {y: {beginAtZero: true, title: {display: true, text: 'Loans'}}, y1: {beginAtZero: true, position: 'right', grid: {drawOnChartArea: false}, title: {display: true, text: 'UGX'}}}}
});
new Chart(document.getElementById('cats'), {
    type: 'doughnut',
    data: {labels: @json($byCategory->pluck('name')), datasets: [{data: @json($byCategory->pluck('total'))}]},
    options: {plugins: {legend: {position: 'bottom'}}}
});
</script>
@endpush
