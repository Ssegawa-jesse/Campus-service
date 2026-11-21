@extends('layouts.app')
@section('title', $saved ? $saved->title : 'Your Smart Itinerary')

@push('styles')<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">@endpush

@php
    $in = $plan['input'];
    $b = $plan['budget'];
    $cat = config('murchison.categories')[$in['category']]['label'];
    $points = collect($plan['days'])->flatMap(fn ($d) => collect($d['items'])->where('kind', 'activity')->filter(fn ($i) => $i['lat'])->map(fn ($i) => ['day' => $d['day'], 'title' => $i['title'], 'lat' => $i['lat'], 'lng' => $i['lng'], 'place' => $i['place']]))->values();
@endphp

@section('content')
<section class="bg-forest py-4">
    <div class="container-xl d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="section-kicker text-gold">Smart Itinerary {{ $saved ? '· '.$saved->reference : '' }}</div>
            <h1 class="fw-bold mb-1">{{ $saved?->title ?? $in['days'].'-day Murchison Falls trip' }}</h1>
            <div class="text-white-50">{{ \Carbon\Carbon::parse($in['start_date'])->format('j M Y') }} · {{ $in['adults'] }} adult(s){{ $in['children'] ? ', '.$in['children'].' child(ren)' : '' }} · {{ $cat }} · {{ $plan['season']['month'] }}: {{ $plan['season']['season'] }} season</div>
        </div>
        <div class="text-end">
            <div class="small text-white-50">Estimated total</div>
            <div class="display-6 fw-bold text-gold">{{ ugx($b['total']) }}</div>
            <div class="small text-white-50">≈ US${{ number_format($b['total_usd']) }} · {{ ugx($b['per_person']) }} per person</div>
        </div>
    </div>
</section>

<div class="container-xl py-4">
    <div class="d-flex flex-wrap gap-2 mb-4 d-print-none">
        @if (! $saved)
            @auth
                <form method="POST" action="{{ route('itinerary.save') }}" class="d-flex gap-2">@csrf
                    <input name="title" class="form-control" placeholder="Name this trip (optional)">
                    <button class="btn btn-gold text-nowrap"><i class="bi bi-save"></i> Save itinerary</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-gold"><i class="bi bi-box-arrow-in-right"></i> Log in to save & book</a>
            @endauth
            <a href="{{ route('itinerary.index') }}" class="btn btn-outline-forest"><i class="bi bi-sliders"></i> Adjust preferences</a>
        @endif
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print / PDF</button>
        <a href="{{ route('budget', ['days' => $in['days'], 'adults' => $in['adults'], 'children' => $in['children'], 'category' => $in['category'], 'tier' => $in['tier'], 'transport' => $in['transport'], 'origin' => $in['origin'], 'activities' => $plan['activity_slugs']]) }}" class="btn btn-outline-secondary"><i class="bi bi-calculator"></i> Open in budget calculator</a>
    </div>

    @foreach ($plan['alerts'] ?? [] as $a)
        <div class="alert alert-{{ \App\Models\TravelUpdate::SEVERITIES[$a['severity']] ?? 'info' }} py-2 small"><i class="bi bi-broadcast"></i> <strong>Travel update:</strong> {{ $a['title'] }} – <a href="{{ route('updates') }}">details</a></div>
    @endforeach
    @foreach ($plan['warnings'] as $w)<div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle"></i> {{ $w }}</div>@endforeach

    <div class="row g-4">
        <div class="col-lg-7">
            @foreach ($plan['days'] as $day)
                <div class="card border-0 shadow-sm mb-3"><div class="card-body">
                    <h5 class="mb-3"><span class="badge bg-forest me-2">Day {{ $day['day'] }}</span>{{ $day['label'] }}</h5>
                    <div class="timeline">
                        @foreach ($day['items'] as $item)
                            <div class="tl-item kind-{{ $item['kind'] }}">
                                @if ($item['kind'] === 'tip')
                                    <div class="small text-muted fst-italic"><i class="bi bi-lightbulb text-gold"></i> {{ $item['detail'] }}</div>
                                @else
                                    <div><span class="tl-time">{{ $item['time'] }}</span> <strong>{{ $item['title'] }}</strong>
                                        @if (! empty($item['cost_ugx']))<span class="badge bg-light text-dark border ms-1">{{ ugx($item['cost_ugx']) }}</span>@endif
                                        @if (! empty($item['has_360']))<a href="{{ route('explore', ['activity' => $item['activity']]) }}" class="badge bg-dark text-decoration-none ms-1 d-print-none"><i class="bi bi-badge-vr"></i> 360° preview</a>@endif
                                    </div>
                                    <div class="small text-muted">{{ $item['detail'] }}@if (! empty($item['place'])) <span class="text-forest">· {{ $item['place'] }}</span>@endif</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div></div>
            @endforeach
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-3"><div class="card-body">
                <h5><i class="bi bi-calculator text-forest"></i> Budget ({{ $cat }})</h5>
                <table class="table table-sm small mb-0">
                    @foreach ($b['lines'] as $l)
                        <tr><td><strong>{{ $l['label'] }}</strong><div class="text-muted">{{ $l['detail'] }}</div></td><td class="text-end text-nowrap">{{ ugx($l['ugx']) }}</td></tr>
                    @endforeach
                    <tr><td>Contingency (10%)<div class="text-muted">Tips, drinks, price changes</div></td><td class="text-end">{{ ugx($b['contingency']) }}</td></tr>
                    <tr class="table-light fw-bold"><td>Total</td><td class="text-end">{{ ugx($b['total']) }}<div class="small fw-normal text-muted">≈ US${{ number_format($b['total_usd']) }}</div></td></tr>
                </table>
                <p class="small text-muted mt-2 mb-0">Exchange rate US$1 = UGX {{ number_format($b['rate']) }}. Indicative UWA planning figures – confirm current tariffs with UWA.</p>
            </div></div>

            <div class="card border-0 shadow-sm mb-3"><div class="card-body p-2"><div id="planMap" style="height:280px;border-radius:.75rem"></div></div></div>

            @if ($saved && auth()->user()?->isTourist() && auth()->id() === $saved->user_id)
                <div class="card border-0 shadow-sm mb-3 d-print-none"><div class="card-body">
                    <h5><i class="bi bi-credit-card text-forest"></i> Book & pay</h5>
                    @if ($saved->bookings->isNotEmpty())
                        @foreach ($saved->bookings as $bk)
                            <div class="small">Booking <a href="{{ route('tourist.bookings.show', $bk) }}">{{ $bk->reference }}</a> – {{ $bk->statusLabel() }}
                                @if ($bk->canBePaid()) <a href="{{ route('payments.checkout', $bk) }}" class="btn btn-sm btn-gold ms-2">Pay now</a>@endif</div>
                        @endforeach
                    @else
                        <form method="POST" action="{{ route('itinerary.book', $saved) }}" class="row g-2">@csrf
                            <div class="col-12"><label class="form-label small">Tour operator to deliver this trip</label>
                                <select name="operator_id" class="form-select" required>@foreach ($operators as $o)<option value="{{ $o->id }}">{{ $o->company_name }}</option>@endforeach</select></div>
                            <div class="col-12"><label class="form-label small">Phone for SMS confirmation</label><input name="contact_phone" class="form-control" value="{{ auth()->user()->phone }}" required></div>
                            <div class="col-12"><button class="btn btn-gold w-100">Book for {{ ugx($b['total']) }} & pay (MTN / Airtel / Visa / Mastercard)</button></div>
                        </form>
                        <p class="small text-muted mt-2 mb-0">Email and SMS confirmations are sent to you and the platform administrator.</p>
                    @endif
                </div></div>
            @elseif ($saved)
                <div class="small text-muted mb-3">Saved {{ $saved->created_at->diffForHumans() }} by {{ $saved->user?->name }}.</div>
            @endif

            <div class="card border-0 shadow-sm mb-3"><div class="card-body small">
                <h6><i class="bi bi-lightbulb text-gold"></i> Why this plan</h6>
                <ul class="mb-2">@foreach ($plan['reasons'] as $r)<li>{{ $r }}</li>@endforeach</ul>
                @if ($plan['skipped'])<h6 class="mt-3">Left out</h6><ul class="mb-2 text-muted">@foreach ($plan['skipped'] as $s)<li>{{ $s }}</li>@endforeach</ul>@endif
                <h6 class="mt-3">Season</h6><p class="mb-0">{{ $plan['season']['month'] }} ({{ $plan['season']['season'] }}): {{ $plan['season']['note'] }}</p>
            </div></div>

            <div class="card border-0 shadow-sm"><div class="card-body small">
                <h6><i class="bi bi-backpack text-forest"></i> Packing list</h6>
                <ul class="mb-0">@foreach ($plan['packing'] as $p)<li>{{ $p }}</li>@endforeach</ul>
            </div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
(() => {
    const pts = {!! Js::from($points) !!};
    const map = L.map('planMap', { scrollWheelZoom: false }).setView([2.2, 31.65], 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
    const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const group = pts.map(p => L.marker([p.lat, p.lng]).bindPopup(`<strong>Day ${p.day}</strong><br>${esc(p.title)}<br><small>${esc(p.place)}</small>`).addTo(map));
    if (group.length) map.fitBounds(L.featureGroup(group).getBounds().pad(0.3));
})();
</script>
@endpush
