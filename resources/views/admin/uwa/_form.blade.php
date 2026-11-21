@php $a = $activity; @endphp
<div class="row g-2">
    <div class="col-md-6"><label class="form-label small">Name</label><input name="name" value="{{ $a->name }}" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><label class="form-label small">Category</label><select name="category" class="form-select form-select-sm">@foreach (\App\Models\Activity::CATEGORIES as $k => $l)<option value="{{ $k }}" @selected($a->category === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small">Map place</label><select name="place_id" class="form-select form-select-sm">@foreach ($places as $p)<option value="{{ $p->id }}" @selected($a->place_id === $p->id)>{{ $p->name }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label small">Description</label><textarea name="description" rows="2" class="form-control form-control-sm" required>{{ $a->description }}</textarea></div>
    <div class="col-md-2 col-6"><label class="form-label small">FNR (US$)</label><input name="price_fnr_usd" value="{{ $a->price_fnr_usd }}" class="form-control form-control-sm" required></div>
    <div class="col-md-2 col-6"><label class="form-label small">FR (US$)</label><input name="price_fr_usd" value="{{ $a->price_fr_usd }}" class="form-control form-control-sm" required></div>
    <div class="col-md-2 col-6"><label class="form-label small">EAC (UGX)</label><input name="price_eac_ugx" value="{{ $a->price_eac_ugx }}" class="form-control form-control-sm" required></div>
    <div class="col-md-2 col-6"><label class="form-label small">Group fee US$</label><input name="group_fee_usd" value="{{ $a->group_fee_usd }}" class="form-control form-control-sm"></div>
    <div class="col-md-2 col-6"><label class="form-label small">Group fee UGX</label><input name="group_fee_ugx" value="{{ $a->group_fee_ugx }}" class="form-control form-control-sm"></div>
    <div class="col-md-2 col-6"><label class="form-label small">Hours</label><input name="hours" value="{{ $a->hours ?? 2 }}" class="form-control form-control-sm" required></div>
    <div class="col-md-2 col-4"><label class="form-label small">Fitness</label><select name="fitness_level" class="form-select form-select-sm">@foreach (\App\Models\Activity::FITNESS as $k => $l)<option value="{{ $k }}" @selected($a->fitness_level == $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-2 col-4"><label class="form-label small">Priority 1–10</label><input name="priority" value="{{ $a->priority ?? 5 }}" class="form-control form-control-sm" required></div>
    <div class="col-md-2 col-4"><label class="form-label small">Min. age</label><input name="min_age" value="{{ $a->min_age }}" class="form-control form-control-sm"></div>
    <div class="col-md-6"><label class="form-label small d-block">Time slots</label>@foreach (array_keys(config('murchison.slots')) as $s)<label class="me-2 small"><input type="checkbox" name="slots[]" value="{{ $s }}" @checked(in_array($s, $a->slots ?? []))> {{ $s }}</label>@endforeach</div>
    <div class="col-12"><label class="form-label small d-block">Matches interests</label>@foreach (config('murchison.interests') as $k => $l)<label class="me-2 small"><input type="checkbox" name="interests[]" value="{{ $k }}" @checked(in_array($k, $a->interests ?? []))> {{ $l }}</label>@endforeach</div>
    <div class="col-md-9"><label class="form-label small">Wet-season note</label><input name="wet_season_note" value="{{ $a->wet_season_note }}" class="form-control form-control-sm"></div>
    <div class="col-md-3 d-flex align-items-end"><label class="small"><input type="checkbox" name="is_active" value="1" @checked($a->is_active ?? true)> Active</label></div>
</div>
