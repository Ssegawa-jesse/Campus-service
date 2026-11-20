<div class="card package-card border-0 shadow-sm h-100">
    <img src="{{ $package->imageUrl() }}" class="card-img-top" alt="{{ $package->title }}" loading="lazy">
    <div class="card-body d-flex flex-column">
        <div class="small text-muted mb-1"><i class="bi bi-geo-alt"></i> {{ $package->park->name }}</div>
        <h5 class="card-title"><a href="{{ route('packages.show', $package) }}" class="text-reset">{{ $package->title }}</a></h5>
        <div class="small text-muted mb-3"><i class="bi bi-patch-check-fill text-forest"></i> {{ $package->operator->company_name }}</div>
        <div class="mt-auto d-flex justify-content-between align-items-center">
            <div><span class="fw-bold text-forest">{{ ugx($package->price_ugx) }}</span><span class="small text-muted"> / person</span></div>
            <span class="badge bg-forest-light text-forest">{{ $package->days }} days</span>
        </div>
    </div>
</div>
