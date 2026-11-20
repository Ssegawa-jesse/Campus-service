@extends('layouts.app')
@section('title', 'Enquiry '.$booking->reference)
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Enquiry {{ $booking->reference }}</h1>@include('partials.booking-status')</div>
<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6>Tourist</h6>
            <p class="mb-1"><strong>{{ $booking->tourist->name }}</strong> ({{ $booking->tourist->country }})</p>
            <p class="small mb-3"><i class="bi bi-telephone"></i> <a href="tel:{{ $booking->contact_phone }}">{{ $booking->contact_phone }}</a> · <i class="bi bi-envelope"></i> <a href="mailto:{{ $booking->tourist->email }}">{{ $booking->tourist->email }}</a></p>
            <dl class="row small mb-0">
                <dt class="col-5">Park</dt><dd class="col-7">{{ $booking->park->name }}</dd>
                @if ($booking->package)<dt class="col-5">Package</dt><dd class="col-7">{{ $booking->package->title }} ({{ ugx($booking->package->price_ugx) }} pp)</dd>@endif
                <dt class="col-5">Travel date</dt><dd class="col-7">{{ $booking->travel_date->format('D d M Y') }}</dd>
                <dt class="col-5">Travellers</dt><dd class="col-7">{{ $booking->group_size }}</dd>
                <dt class="col-5">Message</dt><dd class="col-7">{{ $booking->message ?: '–' }}</dd>
            </dl>
            @foreach ($booking->payments->where('status', 'successful') as $p)
                <div class="alert alert-success small mt-3 mb-0"><i class="bi bi-check-circle"></i> Paid {{ ugx($p->amount) }} via {{ $p->methodLabel() }} on {{ $p->paid_at?->format('d M Y H:i') }} (ref {{ $p->tx_ref }})</div>
            @endforeach
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6>Respond</h6>
            @if (empty($allowed))
                <p class="text-muted small mb-0">No further action needed for this booking.</p>
            @else
                <form method="POST" action="{{ route('operator.bookings.update', $booking) }}" class="row g-3">
                    @csrf @method('PUT')
                    <div class="col-12">
                        <label class="form-label">Action</label>
                        <select name="status" class="form-select" id="statusSel">
                            @foreach ($allowed as $s)
                                <option value="{{ $s }}">{{ ['quoted' => 'Send quote (tourist can pay online)', 'declined' => 'Decline – not available', 'confirmed' => 'Confirm trip', 'completed' => 'Mark trip as completed'][$s] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (in_array('quoted', $allowed))
                        <div class="col-12" id="amountField">
                            <label class="form-label">Total quote (UGX)</label>
                            <input type="number" name="quoted_amount" step="1000" class="form-control" value="{{ old('quoted_amount', $booking->quoted_amount ?? ($booking->package ? $booking->package->price_ugx * $booking->group_size : '')) }}">
                        </div>
                    @endif
                    <div class="col-12"><label class="form-label">Note to tourist</label><textarea name="operator_response" rows="3" class="form-control">{{ old('operator_response', $booking->operator_response) }}</textarea></div>
                    <div class="col-12"><button class="btn btn-forest w-100">Save & notify tourist (SMS + email)</button></div>
                </form>
            @endif
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const sel = document.getElementById('statusSel'), amt = document.getElementById('amountField');
if (sel && amt) { const s = () => amt.classList.toggle('d-none', sel.value !== 'quoted'); sel.addEventListener('change', s); s(); }
</script>
@endpush
