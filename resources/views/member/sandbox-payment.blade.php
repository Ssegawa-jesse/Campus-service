@extends('layouts.app')
@section('title', 'Confirm payment')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-warning"><strong><i class="bi bi-phone"></i> {{ $payment->methodLabel() }} — test checkout</strong></div>
            <div class="card-body text-center">
                <p class="text-muted small">In live mode the member receives a prompt on their phone to enter their Mobile Money PIN. This test page simulates that step.</p>
                <div class="display-6 fw-bold mb-2">UGX {{ number_format($payment->amount) }}</div>
                <p>Reference <code>{{ $payment->reference }}</code>@if($payment->phone)<br>Phone {{ $payment->phone }}@endif</p>
                <form method="POST" action="{{ route('payments.sandbox.complete', $payment) }}" class="d-flex gap-2 justify-content-center">@csrf
                    <button name="outcome" value="approve" class="btn btn-success"><i class="bi bi-check-lg"></i> Approve payment</button>
                    <button name="outcome" value="decline" class="btn btn-outline-danger">Decline</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
