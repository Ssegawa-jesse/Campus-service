{{-- Library-card details: course of study, year (students only) and date of birth. Needs $member (may be new). --}}
<div class="col-md-6">
    <label class="form-label">Course of study / department {{ ($required ?? false) ? '*' : '' }}</label>
    <input name="programme" value="{{ old('programme', $member->programme) }}" class="form-control" placeholder="e.g. Bachelor of Business Computing" @required($required ?? false)>
</div>
<div class="col-md-3 year-field">
    <label class="form-label">Year of study {{ ($required ?? false) ? '*' : '' }}</label>
    <select name="year_of_study" class="form-select">
        <option value="">—</option>
        @foreach(range(1, 5) as $y)<option value="{{ $y }}" @selected(old('year_of_study', $member->year_of_study) == $y)>Year {{ $y }}</option>@endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">Date of birth {{ ($required ?? false) ? '*' : '' }}</label>
    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $member->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->subYears(10)->format('Y-m-d') }}" class="form-control" @required($required ?? false)>
</div>
@push('scripts')
<script>
(() => {
    const type = document.querySelector('[name=type]');
    const year = document.querySelector('.year-field');
    if (!type || !year) return;
    const sync = () => year.style.display = type.value === 'staff' ? 'none' : '';
    type.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
