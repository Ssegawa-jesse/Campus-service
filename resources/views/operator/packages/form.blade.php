@extends('layouts.app')
@section('title', $package->exists ? 'Edit package' : 'New package')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>{{ $package->exists ? 'Edit package' : 'New package' }}</h1></div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="{{ $package->exists ? route('operator.packages.update', $package) : route('operator.packages.store') }}" enctype="multipart/form-data" class="row g-3">
        @csrf @if ($package->exists) @method('PUT') @endif
        <div class="col-md-8"><label class="form-label">Title</label><input name="title" value="{{ old('title', $package->title) }}" class="form-control" required placeholder="e.g. 3-Day Gorilla Trekking Safari"></div>
        <div class="col-md-4"><label class="form-label">Park</label><select name="park_id" class="form-select" required>@foreach ($parks as $p)<option value="{{ $p->id }}" @selected(old('park_id', $package->park_id) == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Days</label><input type="number" name="days" value="{{ old('days', $package->days) }}" min="1" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Price per person (UGX)</label><input type="number" name="price_ugx" value="{{ old('price_ugx', $package->price_ugx) }}" min="1000" step="1000" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Max group size</label><input type="number" name="max_group" value="{{ old('max_group', $package->max_group) }}" min="1" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Description / itinerary</label><textarea name="description" rows="6" class="form-control" required>{{ old('description', $package->description) }}</textarea></div>
        <div class="col-12"><label class="form-label">What's included <small class="text-muted">(one per line)</small></label><textarea name="includes" rows="4" class="form-control">{{ old('includes', $package->includes) }}</textarea></div>
        <div class="col-md-8">
            <label class="form-label">Photo</label><input type="file" name="image" accept="image/*" class="form-control">
            @if ($package->image)<img src="{{ $package->imageUrl() }}" class="rounded mt-2" height="80" alt="">@endif
        </div>
        <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="act" @checked(old('is_active', $package->is_active))><label for="act" class="form-check-label">Visible to tourists</label></div></div>
        <div class="col-12"><button class="btn btn-forest">Save package</button> <a href="{{ route('operator.packages.index') }}" class="btn btn-link">Cancel</a></div>
    </form>
</div></div>
@endsection
