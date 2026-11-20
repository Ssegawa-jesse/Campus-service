@extends('layouts.app')
@section('title', 'Payments')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>Payments</h1>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-forest fs-6">Total received: {{ ugx($total) }}</span>
        <form><select name="status" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">All</option>@foreach (['successful', 'pending', 'failed'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select></form>
    </div>
</div>
<div class="alert alert-light small"><i class="bi bi-gear"></i> Payment gateway: <strong>{{ config('pearl360.payments.driver') }}</strong>. Set <code>PAYMENT_DRIVER=flutterwave</code> and your Flutterwave keys in <code>.env</code> to accept real MTN MoMo, Airtel Money and card payments.</div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table align-middle mb-0 small">
        <thead><tr><th>Tx ref</th><th>Booking</th><th>Payer</th><th>Method</th><th>Amount</th><th>Gateway</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
            @forelse ($payments as $p)
                <tr>
                    <td>{{ $p->tx_ref }}<div class="text-muted">{{ $p->gateway_reference }}</div></td>
                    <td>{{ $p->booking->reference }}<div class="text-muted">{{ $p->booking->park->name }}</div></td>
                    <td>{{ $p->user->name }}<div class="text-muted">{{ $p->phone }}</div></td>
                    <td>{{ $p->methodLabel() }}</td>
                    <td class="fw-semibold">{{ ugx($p->amount) }}</td>
                    <td>{{ $p->gateway }}</td>
                    <td><span class="badge bg-{{ $p->status === 'successful' ? 'success' : ($p->status === 'pending' ? 'secondary' : 'danger') }}">{{ $p->status }}</span></td>
                    <td>{{ ($p->paid_at ?? $p->created_at)->format('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No payments yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
