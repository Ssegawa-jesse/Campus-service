@extends('layouts.app')
@section('title', 'Approve payment')

@section('content')
<div class="container py-5">
    <div class="text-center mb-4">
        <span class="badge bg-warning text-dark"><i class="bi bi-cone-striped"></i> Payment simulator (demo mode)</span>
        <p class="small text-muted mt-2">In live mode this prompt appears on the customer's phone via MTN / Airtel. Set <code>PAYMENT_DRIVER=flutterwave</code> in .env to go live.</p>
    </div>
    <div class="phone-mock">
        <div class="screen">
            @php $isCard = $payment->method === 'card'; @endphp
            <div class="text-center mb-3">
                <span class="pay-logo {{ $isCard ? 'card' : ($payment->method === 'mobile_money_airtel' ? 'airtel' : 'mtn') }} mx-auto">{{ $isCard ? 'VISA' : ($payment->method === 'mobile_money_airtel' ? 'airtel' : 'MTN') }}</span>
                <h6 class="mt-2 mb-0">{{ $payment->methodLabel() }}</h6>
            </div>
            <p class="small">Pay <strong>{{ ugx($payment->amount) }}</strong> to <strong>{{ config('pearl360.name') }}</strong> for booking {{ $payment->booking->reference }} ({{ $payment->booking->park->name }}).</p>
            @if (! $isCard)<p class="small text-muted">Number: {{ $payment->phone }}</p>@endif
            <form method="POST" action="{{ route('payments.simulate.complete', $payment->tx_ref) }}">
                @csrf
                <label class="form-label small">{{ $isCard ? 'Card OTP' : 'Enter PIN' }} (any 4–5 digits)</label>
                <input name="pin" type="password" inputmode="numeric" maxlength="5" class="form-control form-control-lg text-center mb-3" autofocus>
                <button name="decision" value="approve" class="btn btn-success w-100 mb-2">Approve</button>
                <button name="decision" value="decline" class="btn btn-outline-danger w-100" formnovalidate>Decline</button>
            </form>
            <p class="small text-muted text-center mt-3 mb-0">Ref: {{ $payment->tx_ref }}</p>
        </div>
    </div>
</div>
@endsection
