<div class="card park-card h-100 border-0 shadow-sm">
    <a href="{{ route('parks.show', $park) }}" class="park-card-img" style="background-image:url('{{ $park->coverUrl() }}')">
        <span class="badge bg-dark bg-opacity-75 region">{{ $park->region }}</span>
        @if ($park->is_unesco)<span class="badge bg-gold unesco"><i class="bi bi-award"></i> UNESCO</span>@endif
    </a>
    <div class="card-body d-flex flex-column">
        <h5 class="card-title mb-1"><a href="{{ route('parks.show', $park) }}" class="stretched-link-none">{{ $park->name }}</a></h5>
        <p class="small text-muted mb-2">{{ $park->tagline }}</p>
        <div class="d-flex flex-wrap gap-1 mb-3">
            @foreach (array_slice($park->interestLabels(), 0, 3) as $label)
                <span class="chip">{{ $label }}</span>
            @endforeach
        </div>
        <div class="mt-auto d-flex justify-content-between align-items-center small">
            <span class="text-muted"><i class="bi bi-cash-stack me-1"></i>from ${{ number_format($park->min_cost) }}</span>
            @if (($park->reviews_avg_rating ?? null))
                <span class="text-gold"><i class="bi bi-star-fill"></i> {{ number_format($park->reviews_avg_rating, 1) }}</span>
            @endif
        </div>
    </div>
    <div class="card-footer bg-transparent border-0 pt-0 pb-3 d-flex gap-2">
        <a href="{{ route('tour.show', $park) }}" class="btn btn-sm btn-forest flex-fill"><i class="bi bi-badge-vr me-1"></i>360° tour</a>
        <a href="{{ route('parks.show', $park) }}" class="btn btn-sm btn-outline-forest flex-fill">Details</a>
    </div>
</div>
