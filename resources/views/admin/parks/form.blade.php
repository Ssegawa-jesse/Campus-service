@extends('layouts.app')
@section('title', $park->exists ? 'Edit '.$park->name : 'Add park')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>{{ $park->exists ? $park->name : 'Add a park' }}</h1>
    @if ($park->exists)
        <div class="d-flex gap-2">
            <a href="{{ route('admin.media.index', $park) }}" class="btn btn-outline-forest"><i class="bi bi-images"></i> Photos & videos</a>
            <a href="{{ route('admin.scenes.index', $park) }}" class="btn btn-outline-forest"><i class="bi bi-badge-vr"></i> 360° scenes</a>
        </div>
    @endif
</div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
    <form method="POST" action="{{ $park->exists ? route('admin.parks.update', $park) : route('admin.parks.store') }}" enctype="multipart/form-data" class="row g-3">
        @csrf @if ($park->exists) @method('PUT') @endif
        <div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name', $park->name) }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Type</label><select name="type" class="form-select">@foreach (\App\Models\Park::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type', $park->type) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Region</label><input name="region" value="{{ old('region', $park->region) }}" class="form-control" required list="regions"><datalist id="regions">@foreach (['Western', 'South-western', 'North-western', 'North-eastern', 'Eastern', 'Central', 'Northern'] as $r)<option value="{{ $r }}">@endforeach</datalist></div>
        <div class="col-12"><label class="form-label">Tagline</label><input name="tagline" value="{{ old('tagline', $park->tagline) }}" class="form-control"></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="6" class="form-control" required>{{ old('description', $park->description) }}</textarea></div>
        <div class="col-12"><label class="form-label">How to get there</label><textarea name="how_to_get_there" rows="2" class="form-control">{{ old('how_to_get_there', $park->how_to_get_there) }}</textarea></div>

        <div class="col-md-3"><label class="form-label">Min. cost (USD)</label><input type="number" step="0.01" name="min_cost" value="{{ old('min_cost', $park->min_cost) }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Difficulty</label><select name="difficulty_level" class="form-select">@foreach (\App\Models\Park::DIFFICULTY as $k => $v)<option value="{{ $k }}" @selected(old('difficulty_level', $park->difficulty_level) == $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Min. days</label><input type="number" name="min_days" value="{{ old('min_days', $park->min_days) }}" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Area (km²)</label><input type="number" name="area_km2" value="{{ old('area_km2', $park->area_km2) }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Gazetted</label><input type="number" name="established" value="{{ old('established', $park->established) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Distance from Kampala (km)</label><input type="number" name="distance_from_kampala_km" value="{{ old('distance_from_kampala_km', $park->distance_from_kampala_km) }}" class="form-control"></div>
        <div class="col-md-8"><label class="form-label">Best time (text)</label><input name="best_time" value="{{ old('best_time', $park->best_time) }}" class="form-control"></div>

        <div class="col-12">
            <label class="form-label">Best months <small class="text-muted">(used by the trip planner)</small></label>
            <div class="d-flex flex-wrap gap-3">
                @foreach (month_names() as $n => $m)
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="best_months[]" value="{{ $n }}" id="m{{ $n }}" @checked(in_array($n, old('best_months', $park->best_months ?? [])))><label class="form-check-label small" for="m{{ $n }}">{{ substr($m, 0, 3) }}</label></div>
                @endforeach
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Interests / attractions <small class="text-muted">(used by the trip planner)</small></label>
            <div class="row g-1">
                @foreach (\App\Models\Park::INTERESTS as $k => $v)
                    <div class="col-md-3 form-check"><input class="form-check-input" type="checkbox" name="interests[]" value="{{ $k }}" id="int{{ $k }}" @checked(in_array($k, old('interests', $park->interests ?? [])))><label class="form-check-label small" for="int{{ $k }}">{{ $v }}</label></div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Map location <small class="text-muted">– click on the map to set the pin</small></label>
            <div id="pickMap" style="height:300px" class="rounded mb-2"></div>
            <div class="row g-2">
                <div class="col"><input name="latitude" id="lat" value="{{ old('latitude', $park->latitude) }}" class="form-control" required placeholder="Latitude"></div>
                <div class="col"><input name="longitude" id="lng" value="{{ old('longitude', $park->longitude) }}" class="form-control" required placeholder="Longitude"></div>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Cover photo</label>
            <input type="file" name="cover_image" accept="image/*" class="form-control">
            @if ($park->cover_image)<img src="{{ $park->coverUrl() }}" class="rounded mt-2" height="90" alt="">@endif
        </div>
        <div class="col-md-6">
            <label class="form-label">Feature video – YouTube link <em>or</em> upload MP4 (max 50 MB)</label>
            <input name="video_url" value="{{ old('video_url', preg_match('#^https?://#', (string) $park->video_url) ? $park->video_url : '') }}" class="form-control mb-2" placeholder="https://www.youtube.com/watch?v=…">
            <input type="file" name="video_file" accept="video/mp4,video/webm" class="form-control">
            @if ($park->video_url)<div class="small text-muted mt-1">Current: {{ Str::limit($park->video_url, 60) }}</div>@endif
        </div>
        <div class="col-12 d-flex gap-4">
            <div class="form-check form-switch"><input type="hidden" name="is_published" value="0"><input class="form-check-input" type="checkbox" name="is_published" value="1" id="pub" @checked(old('is_published', $park->is_published))><label class="form-check-label" for="pub">Published</label></div>
            <div class="form-check form-switch"><input type="hidden" name="is_unesco" value="0"><input class="form-check-input" type="checkbox" name="is_unesco" value="1" id="un" @checked(old('is_unesco', $park->is_unesco))><label class="form-check-label" for="un">UNESCO World Heritage Site</label></div>
        </div>
        <div class="col-12"><button class="btn btn-forest btn-lg">Save park</button></div>
    </form>
</div></div>

@if ($park->exists)
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h5>Activities</h5>
    <table class="table table-sm align-middle small">
        <thead><tr><th>Name</th><th>Category</th><th>Fitness</th><th>Duration</th><th>Cost (USD)</th><th></th></tr></thead>
        <tbody>
            @foreach ($park->activities as $a)
                @php $f = 'act-'.$a->id; @endphp
                <tr>
                    <td><input form="{{ $f }}" name="name" value="{{ $a->name }}" class="form-control form-control-sm"><input form="{{ $f }}" type="hidden" name="description" value="{{ $a->description }}"></td>
                    <td><select form="{{ $f }}" name="category" class="form-select form-select-sm">@foreach (\App\Models\Activity::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected($a->category === $k)>{{ $v }}</option>@endforeach</select></td>
                    <td><select form="{{ $f }}" name="fitness_level" class="form-select form-select-sm">@foreach (\App\Models\Activity::FITNESS as $k => $v)<option value="{{ $k }}" @selected($a->fitness_level == $k)>{{ $v }}</option>@endforeach</select></td>
                    <td><input form="{{ $f }}" name="duration" value="{{ $a->duration }}" class="form-control form-control-sm"></td>
                    <td><input form="{{ $f }}" type="number" step="0.01" name="indicative_cost" value="{{ $a->indicative_cost }}" class="form-control form-control-sm" style="width:90px"></td>
                    <td class="text-nowrap">
                        <form id="{{ $f }}" method="POST" action="{{ route('admin.activities.update', $a) }}" class="d-inline">@csrf @method('PUT')<button class="btn btn-sm btn-outline-forest" title="Save"><i class="bi bi-check"></i></button></form>
                        <form method="POST" action="{{ route('admin.activities.destroy', $a) }}" class="d-inline" data-confirm="Remove activity?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button></form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <form method="POST" action="{{ route('admin.activities.store', $park) }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-3"><label class="form-label small">New activity</label><input name="name" class="form-control form-control-sm" required></div>
        <div class="col-md-2"><select name="category" class="form-select form-select-sm">@foreach (\App\Models\Activity::CATEGORIES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="fitness_level" class="form-select form-select-sm">@foreach (\App\Models\Activity::FITNESS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-2"><input name="duration" class="form-control form-control-sm" placeholder="Duration"></div>
        <div class="col-md-1"><input type="number" name="indicative_cost" class="form-control form-control-sm" placeholder="$" required></div>
        <div class="col-md-2"><button class="btn btn-sm btn-gold w-100">Add</button></div>
        <div class="col-12"><input name="description" class="form-control form-control-sm" placeholder="Short description (optional)"></div>
    </form>
</div></div>
@endif
@endsection

@push('styles')<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">@endpush
@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
const lat = document.getElementById('lat'), lng = document.getElementById('lng');
const pm = L.map('pickMap').setView([lat.value || 1.37, lng.value || 32.29], 7);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(pm);
const pin = L.marker([lat.value || 1.37, lng.value || 32.29], { draggable: true }).addTo(pm);
const set = ll => { lat.value = ll.lat.toFixed(6); lng.value = ll.lng.toFixed(6); pin.setLatLng(ll); };
pm.on('click', e => set(e.latlng)); pin.on('dragend', () => set(pin.getLatLng()));
</script>
@endpush
