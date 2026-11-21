@extends('layouts.app')
@section('title', $place->exists ? 'Edit place' : 'Add place')
@section('dashboard', true)
@push('styles')<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">@endpush

@section('content')
<div class="page-head"><h1>{{ $place->exists ? 'Edit '.$place->name : 'Add a map place' }}</h1><a href="{{ route('admin.places.index') }}" class="btn btn-link">← All places</a></div>
<form method="POST" action="{{ $place->exists ? route('admin.places.update', $place) : route('admin.places.store') }}" class="card border-0 shadow-sm"><div class="card-body row g-3">
    @csrf @if ($place->exists) @method('PUT') @endif
    <div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name', $place->name) }}" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Type</label><select name="type" class="form-select">@foreach (\App\Models\Place::TYPES as $k => [$l]) <option value="{{ $k }}" @selected(old('type', $place->type) === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Bank / zone</label><select name="zone" class="form-select">@foreach (\App\Models\Place::ZONES as $k => $l)<option value="{{ $k }}" @selected(old('zone', $place->zone) === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-12"><div id="placeMap"></div><div class="small text-muted mt-1"><i class="bi bi-cursor"></i> Click on the map to set the coordinates.</div></div>
    <div class="col-md-3"><label class="form-label">Latitude</label><input name="latitude" id="lat" value="{{ old('latitude', $place->latitude) }}" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Longitude</label><input name="longitude" id="lng" value="{{ old('longitude', $place->longitude) }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Opening hours</label><input name="opening_hours" value="{{ old('opening_hours', $place->opening_hours) }}" class="form-control"></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control">{{ old('description', $place->description) }}</textarea></div>
    <div class="col-md-9"><label class="form-label">Visitor tip</label><input name="tip" value="{{ old('tip', $place->tip) }}" class="form-control"></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $place->is_active))><label class="form-check-label" for="act">Show on map</label></div></div>
    <div class="col-12"><button class="btn btn-gold">Save place</button></div>
</div></form>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
const lat = document.getElementById('lat'), lng = document.getElementById('lng');
const map = L.map('placeMap').setView([lat.value || 2.2, lng.value || 31.7], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
const m = L.marker([lat.value || 2.2, lng.value || 31.7], { draggable: true }).addTo(map);
const set = ll => { m.setLatLng(ll); lat.value = ll.lat.toFixed(6); lng.value = ll.lng.toFixed(6); };
map.on('click', e => set(e.latlng)); m.on('dragend', () => set(m.getLatLng()));
</script>
@endpush
