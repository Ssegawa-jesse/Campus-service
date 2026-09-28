@extends('layouts.app')
@section('title', 'New booking enquiry')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Booking enquiry</h1></div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        @if ($package)
            <div class="alert alert-light d-flex gap-3 align-items-center">
                <img src="{{ $package->imageUrl() }}" width="90" height="60" class="rounded object-fit-cover" alt="">
                <div><strong>{{ $package->title }}</strong><div class="small text-muted">{{ $package->operator->company_name }} · {{ $package->days }} days · {{ ugx($package->price_ugx) }} per person</div></div>
            </div>
        @endif
        <form method="POST" action="{{ route('tourist.bookings.store') }}" class="row g-3">
            @csrf
            @if ($package)<input type="hidden" name="tour_package_id" value="{{ $package->id }}">@endif
            <div class="col-md-6">
                <label class="form-label">Park</label>
                <select name="park_id" id="parkSelect" class="form-select" required @disabled($package)>
                    <option value="">Choose a park</option>
                    @foreach ($parks as $p)<option value="{{ $p->id }}" @selected(old('park_id', $park?->id) == $p->id)>{{ $p->name }}</option>@endforeach
                </select>
                @if ($package)<input type="hidden" name="park_id" value="{{ $package->park_id }}">@endif
            </div>
            <div class="col-md-6">
                <label class="form-label">Tour operator</label>
                <select name="operator_id" id="operatorSelect" class="form-select" required @disabled($package)>
                    <option value="">Choose an operator</option>
                    @foreach ($operators as $o)<option value="{{ $o->id }}" data-parks="{{ $o->parks->pluck('id')->implode(',') }}" @selected(old('operator_id', $selectedOperator) == $o->id)>{{ $o->company_name }}</option>@endforeach
                </select>
                @if ($package)<input type="hidden" name="operator_id" value="{{ $package->operator_id }}">@endif
                <div class="form-text" id="opHint"></div>
            </div>
            <div class="col-md-4"><label class="form-label">Travel date</label><input type="date" name="travel_date" value="{{ old('travel_date', now()->addMonth()->format('Y-m-d')) }}" min="{{ now()->addDay()->format('Y-m-d') }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Number of travellers</label><input type="number" name="group_size" value="{{ old('group_size', 2) }}" min="1" max="50" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Phone for SMS updates</label><input name="contact_phone" value="{{ old('contact_phone', auth()->user()->phone) }}" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Message to the operator</label><textarea name="message" rows="4" class="form-control" placeholder="Accommodation preference, special requests, activities you want…">{{ old('message') }}</textarea></div>
            <div class="col-12"><button class="btn btn-forest btn-lg"><i class="bi bi-send"></i> Send enquiry</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const ps = document.getElementById('parkSelect'), os = document.getElementById('operatorSelect'), hint = document.getElementById('opHint');
function filterOps() {
    if (ps.disabled) return;
    let n = 0;
    [...os.options].forEach(o => {
        if (!o.value) return;
        const ok = !ps.value || o.dataset.parks.split(',').includes(ps.value);
        o.hidden = !ok; if (ok) n++;
        if (!ok && o.selected) os.value = '';
    });
    hint.textContent = ps.value ? `${n} verified operator(s) serve this park.` : '';
}
ps.addEventListener('change', filterOps); filterOps();
</script>
@endpush
