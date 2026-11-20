@extends('layouts.app')
@section('title', 'My packages')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>My packages</h1><a href="{{ route('operator.packages.create') }}" class="btn btn-gold"><i class="bi bi-plus-lg"></i> New package</a></div>
<div class="row g-3">
    @forelse ($packages as $package)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="row g-0 h-100">
                    <div class="col-4" style="background:url('{{ $package->imageUrl() }}') center/cover"></div>
                    <div class="col-8"><div class="card-body">
                        <h6 class="mb-1">{{ $package->title }}</h6>
                        <div class="small text-muted mb-2">{{ $package->park->name }} · {{ $package->days }} days · {{ ugx($package->price_ugx) }}</div>
                        <span class="badge {{ $package->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $package->is_active ? 'Active' : 'Hidden' }}</span>
                        <span class="small text-muted ms-2"><i class="bi bi-eye"></i> {{ $package->views_count }}</span>
                        <div class="mt-2 d-flex gap-2">
                            <a href="{{ route('operator.packages.edit', $package) }}" class="btn btn-sm btn-outline-forest">Edit</a>
                            <form method="POST" action="{{ route('operator.packages.destroy', $package) }}" data-confirm="Delete this package?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </div>
                    </div></div>
                </div>
            </div>
        </div>
    @empty
        <p class="text-muted">No packages yet. Create your first safari package.</p>
    @endforelse
</div>
@endsection
