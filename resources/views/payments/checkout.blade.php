@extends('layouts.app')
@section('title', 'Pay for '.$booking->reference)

@section('content')
<div class="container py-5" style="max-width:880px">
    <h1 class="fw-bold mb-4">Secure payment</h1>
    <div class="row g-4">
        <div class="col-md-7">
            <form method="POST" action="{{ route('payments.pay', $booking) }}" class="card border-0 shadow-sm">
                @csrf
                <div class="card-body p-4">
                    <h5 class="mb-3">Choose a payment method</h5>
                    @foreach (['mobile_money_mtn' => ['mtn', 'MTN', 'MTN Mobile Money', 'Approve with your MoMo PIN'], 'mobile_money_airtel' => ['airtel', 'airtel', 'Airtel Money', 'Approve with your Airtel Money PIN'], 'card' => ['card', 'VISA', 'Visa / Mastercard', 'Pay securely by card']] as $value => [$cls, $abbr, $title, $sub])
                        <div class="pay-method mb-2">
                            <input type="radio" name="method" value="{{ $value }}" id="m-{{ $value }}" @checked(old('method', 'mobile_money_mtn') === $value)>
                            <label for="m-{{ $value }}"><span class="pay-logo {{ $cls }}">{{ $abbr }}</span><span><strong>{{ $title }}</strong><br><span class="small text-muted">{{ $sub }}</span></span></label>
                        </div>
                    @endforeach
                    <div class="mt-3" id="phoneField">
                        <label class="form-label">Mobile money number</label>
                        <input name="phone" class="form-control form-control-lg" value="{{ old('phone', $booking->contact_phone) }}" placeholder="2567XXXXXXXX">
                    </div>
                    <button class="btn btn-gold btn-lg w-100 mt-4"><i class="bi bi-lock-fill"></i> Pay {{ ugx($booking->quoted_amount) }}</button>
                    <p class="small text-muted text-center mt-2 mb-0">
                        @if (config('pearl360.payments.driver') === 'simulate')
                            <i class="bi bi-cone-striped"></i> Demo mode – no real money will be charged.
                        @else
                            <i class="bi bi-shield-lock"></i> Processed securely by Flutterwave.
                        @endif
                    </p>
                </div>
            </form>
        </div>
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <img src="{{ $booking->park->coverUrl() }}" class="card-img-top" style="height:150px;object-fit:cover" alt="">
                <div class="card-body">
                    <h5 class="mb-1">{{ $booking->park->name }}</h5>
                    <div class="small text-muted mb-3">{{ $booking->operator->company_name }}</div>
                    <dl class="row small mb-0">
                        <dt class="col-6">Reference</dt><dd class="col-6">{{ $booking->reference }}</dd>
                        @if ($booking->package)<dt class="col-6">Package</dt><dd class="col-6">{{ $booking->package->title }}</dd>@endif
                        <dt class="col-6">Travel date</dt><dd class="col-6">{{ $booking->travel_date->format('d M Y') }}</dd>
                        <dt class="col-6">Travellers</dt><dd class="col-6">{{ $booking->group_size }}</dd>
                        <dt class="col-6 fs-6">Total</dt><dd class="col-6 fs-6 fw-bold text-forest">{{ ugx($booking->quoted_amount) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const syncPhone = () => document.getElementById('phoneField').classList.toggle('d-none', document.querySelector('input[name=method]:checked').value === 'card');
document.querySelectorAll('input[name=method]').forEach(r => r.addEventListener('change', syncPhone)); syncPhone();
</script>
@endpush
