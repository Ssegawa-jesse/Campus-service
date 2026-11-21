@extends('layouts.app')
@section('title', $update->exists ? 'Edit travel update' : 'New travel update')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>{{ $update->exists ? 'Edit travel update' : 'New travel update' }}</h1><a href="{{ route('admin.travel-updates.index') }}" class="btn btn-link">← All updates</a></div>
<form method="POST" action="{{ $update->exists ? route('admin.travel-updates.update', $update) : route('admin.travel-updates.store') }}" class="card border-0 shadow-sm"><div class="card-body row g-3">
    @csrf @if ($update->exists) @method('PUT') @endif
    <div class="col-md-4"><label class="form-label">Category</label><select name="category" class="form-select">@foreach (\App\Models\TravelUpdate::CATEGORIES as $k => [$l]) <option value="{{ $k }}" @selected(old('category', $update->category) === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Severity</label><select name="severity" class="form-select">@foreach (array_keys(\App\Models\TravelUpdate::SEVERITIES) as $k)<option value="{{ $k }}" @selected(old('severity', $update->severity) === $k)>{{ ucfirst($k) }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Location</label><input name="location" value="{{ old('location', $update->location) }}" class="form-control" placeholder="e.g. Paraa Jetty"></div>
    <div class="col-12"><label class="form-label">Title</label><input name="title" value="{{ old('title', $update->title) }}" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Details</label><textarea name="body" rows="5" class="form-control" required>{{ old('body', $update->body) }}</textarea></div>
    <div class="col-md-3"><label class="form-label">Valid from</label><input type="date" name="valid_from" value="{{ old('valid_from', $update->valid_from?->toDateString()) }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Valid until</label><input type="date" name="valid_until" value="{{ old('valid_until', $update->valid_until?->toDateString()) }}" class="form-control"></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $update->is_active))><label for="act" class="form-check-label">Live</label></div></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_pinned" value="1" class="form-check-input" id="pin" @checked(old('is_pinned', $update->is_pinned))><label for="pin" class="form-check-label">Pinned to top</label></div></div>
    <div class="col-12"><button class="btn btn-gold">Save update</button></div>
</div></form>
@endsection
