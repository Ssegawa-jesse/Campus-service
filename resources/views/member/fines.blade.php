@extends('layouts.app')
@section('title', 'Fines & payments')
@section('content')
<h3 class="mb-3">Fines &amp; payments</h3>
@unless($live)
    <div class="alert alert-info small"><i class="bi bi-info-circle"></i> Payments are in <strong>sandbox (test) mode</strong>: no real money is charged.</div>
@endunless
<div class="card shadow-sm mb-4">
    <table class="table mb-0">
        <thead><tr><th>Book</th><th>Amount</th><th>Status</th><th style="width:45%">Pay</th></tr></thead>
        <tbody>
        @forelse($fines as $fine)
            <tr>
                <td>{{ $fine->loan?->copy->book->title ?? 'Library charge' }}
                    @if($fine->loan)<br><small class="text-muted">Due {{ $fine->loan->due_at->format('d M Y') }}, {{ $fine->loan->returned_at ? 'returned '.$fine->loan->returned_at->format('d M Y') : 'not yet returned' }}</small>@endif</td>
                <td>UGX {{ number_format($fine->amount) }}</td>
                <td><span class="badge bg-{{ ['unpaid' => 'danger', 'paid' => 'success', 'waived' => 'secondary'][$fine->status] }}">{{ ucfirst($fine->status) }}</span>
                    @if($fine->status === 'waived' && $fine->reason)<br><small class="text-muted">{{ $fine->reason }}</small>@endif</td>
                <td>
                    @if($fine->status === 'unpaid' && $fine->loan && ! $fine->loan->returned_at)
                        <small class="text-muted">Return the book first — the fine grows each day until it is returned.</small>
                    @elseif($fine->status === 'unpaid')
                        <form method="POST" action="{{ route('my.fines.pay', $fine) }}" class="row g-1">@csrf
                            <div class="col-sm-4">
                                <select name="method" class="form-select form-select-sm">
                                    <option value="mtn">MTN MoMo</option><option value="airtel">Airtel Money</option><option value="card">Card</option>
                                </select>
                            </div>
                            <div class="col-sm-5"><input name="phone" value="{{ auth()->user()->phone }}" class="form-control form-control-sm" placeholder="07XXXXXXXX"></div>
                            <div class="col-sm-3 d-grid"><button class="btn btn-sm btn-success">Pay</button></div>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-3">No fines. Keep returning books on time!</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<h5>Payment history</h5>
<div class="card shadow-sm">
    <table class="table mb-0">
        <thead><tr><th>Date</th><th>Reference</th><th>Method</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($payments as $p)
            <tr>
                <td>{{ $p->created_at->format('d M Y H:i') }}</td><td>{{ $p->reference }}</td><td>{{ $p->methodLabel() }}</td>
                <td>UGX {{ number_format($p->amount) }}</td>
                <td><span class="badge bg-{{ ['successful' => 'success', 'pending' => 'warning text-dark', 'failed' => 'danger'][$p->status] }}">{{ ucfirst($p->status) }}</span></td>
                <td>@if($p->status === 'successful')<a href="{{ route('my.payments.receipt', $p) }}" class="small">Receipt</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-3">No payments yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
