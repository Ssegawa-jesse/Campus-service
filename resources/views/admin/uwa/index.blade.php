@extends('layouts.app')
@section('title', 'Activities & UWA prices')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1><i class="bi bi-cash-stack"></i> Activities & UWA prices</h1><span class="small text-muted">US$1 = UGX {{ number_format(setting('usd_rate', config('pearl360.usd_rate'))) }} (change in Settings)</span></div>
<p class="small text-muted">Prices follow the UWA three-tier tariff. The Smart Itinerary engine uses the time slots, hours, fitness, interests and priority to choose and schedule activities. Park entry fees and accommodation tiers are in <code>config/murchison.php</code>.</p>

<div class="card border-0 shadow-sm mb-4"><div class="table-responsive"><table class="table table-sm align-middle mb-0 price-table">
    <thead><tr><th>Activity</th><th>FNR</th><th>FR</th><th>EAC</th><th>Slots</th><th>360°</th><th></th></tr></thead>
    @foreach ($activities as $a)
        <tr class="{{ $a->is_active ? '' : 'text-muted' }}">
            <td><strong>{{ $a->name }}</strong><div class="small text-muted">{{ $a->place?->name }} · {{ $a->hours }} h · priority {{ $a->priority }}</div></td>
            <td>{{ $a->priceLabel('fnr') }}</td><td>{{ $a->priceLabel('fr') }}</td><td>{{ $a->priceLabel('eac') }}</td>
            <td class="small">{{ implode(', ', $a->slots ?? []) }}</td><td>{{ $a->scenes_count }}</td>
            <td><button class="btn btn-sm btn-outline-forest" data-bs-toggle="collapse" data-bs-target="#edit-{{ $a->id }}">Edit</button></td>
        </tr>
        <tr class="collapse" id="edit-{{ $a->id }}"><td colspan="7" class="bg-light">
            <form method="POST" action="{{ route('admin.uwa-activities.update', $a) }}" class="p-2">@csrf @method('PUT')
                @include('admin.uwa._form', ['activity' => $a])
                <button class="btn btn-sm btn-gold mt-2">Save {{ $a->name }}</button>
            </form>
        </td></tr>
    @endforeach
</table></div></div>

<div class="card border-0 shadow-sm"><div class="card-body">
    <h5>Add an activity</h5>
    <form method="POST" action="{{ route('admin.uwa-activities.store') }}">@csrf
        @include('admin.uwa._form', ['activity' => new \App\Models\Activity(['is_active' => true, 'slots' => ['morning'], 'hours' => 2, 'priority' => 5, 'fitness_level' => 1])])
        <button class="btn btn-sm btn-gold mt-2">Add activity</button>
    </form>
</div></div>
@endsection
