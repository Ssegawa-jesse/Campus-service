@extends('layouts.app')
@section('title', 'Receipt '.$payment->reference)
@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <i class="bi bi-receipt fs-1 text-success"></i>
                    <h4 class="mb-0">Payment receipt</h4>
                    <small class="text-muted">{{ \App\Support\LibrarySettings::get('library_name') }}</small>
                </div>
                <table class="table table-sm">
                    <tr><th>Receipt no.</th><td>{{ $payment->reference }}</td></tr>
                    <tr><th>Date</th><td>{{ $payment->paid_at->format('d M Y H:i') }}</td></tr>
                    <tr><th>Member</th><td>{{ $payment->member->user->name }} ({{ $payment->member->member_no }})</td></tr>
                    <tr><th>For</th><td>Overdue fine{{ $payment->fine->loan ? ': '.$payment->fine->loan->copy->book->title : '' }}</td></tr>
                    <tr><th>Method</th><td>{{ $payment->methodLabel() }}</td></tr>
                    @if($payment->gateway_ref)<tr><th>Transaction ID</th><td>{{ $payment->gateway_ref }}</td></tr>@endif
                    <tr class="fs-5"><th>Amount</th><td><strong>UGX {{ number_format($payment->amount) }}</strong></td></tr>
                </table>
                <div class="d-flex gap-2 no-print">
                    <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print</button>
                    <a href="{{ route('my.fines') }}" class="btn btn-lib">Back to fines</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
