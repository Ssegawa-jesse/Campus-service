@php $itPlan = $booking->itineraryPlan(); @endphp
@if ($itPlan)
    <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h5 class="mb-1"><i class="bi bi-magic text-gold"></i> Smart itinerary sent with this booking</h5>
            <a href="{{ route('plan.index', ['p' => $booking->itinerary]) }}" target="_blank" class="btn btn-sm btn-outline-forest">Open full itinerary</a>
        </div>
        <p class="small text-muted">{{ $itPlan['input']['adults'] }} adult(s){{ $itPlan['input']['children'] ? ', '.$itPlan['input']['children'].' child(ren)' : '' }} · {{ config('murchison.tiers.'.$itPlan['input']['tier'].'.label') }} accommodation · from {{ config('murchison.origins.'.$itPlan['input']['origin'].'.label') }} · system estimate <strong>{{ \App\Services\Murchison\TripCost::ugx($itPlan['total']) }}</strong></p>
        <div class="row g-3">
            @foreach ($itPlan['days'] as $d)
                <div class="col-md-6 col-xl-4"><div class="small"><strong>Day {{ $d['day'] }}: {{ $d['title'] }}</strong>
                    <ul class="ps-3 mb-0">@foreach ($d['items'] as $it)@if ($it['kind'] !== 'tip')<li>{{ $it['time'] ? $it['time'].' – ' : '' }}{{ $it['title'] }}</li>@endif @endforeach</ul></div></div>
            @endforeach
        </div>
    </div></div>
@endif
