{{--
    Shared reservation form used by create and edit.
    Expects: $reservation (a Reservation model, empty when creating), $programmes, $action, $method, $submitLabel
--}}
<form action="{{ $action }}" method="POST" class="form-card" novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="form-body">

        @if ($errors->any())
            <div class="flash flash-error">
                Please fix the following and submit again:
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="section-label">Student details</p>

        <div class="field">
            <label for="student_name">Full name <span class="req">*</span></label>
            <input id="student_name" name="student_name" type="text" required
                   class="input-field @error('student_name') is-invalid @enderror"
                   value="{{ old('student_name', $reservation->student_name) }}"
                   placeholder="e.g. Aisha Namuli">
            @error('student_name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-grid form-grid-2">
            <div class="field">
                <label for="registration_number">Registration number <span class="req">*</span></label>
                <input id="registration_number" name="registration_number" type="text" required
                       class="input-field @error('registration_number') is-invalid @enderror"
                       value="{{ old('registration_number', $reservation->registration_number) }}"
                       placeholder="e.g. 22/U/5432/PS">
                @error('registration_number') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="programme">Programme <span class="req">*</span></label>
                <select id="programme" name="programme" required
                        class="input-field @error('programme') is-invalid @enderror">
                    <option value="">Select a programme</option>
                    @foreach ($programmes as $programme)
                        <option value="{{ $programme }}" @selected(old('programme', $reservation->programme) === $programme)>{{ $programme }}</option>
                    @endforeach
                </select>
                @error('programme') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <p class="section-label spaced">Book details</p>

        <div class="form-grid form-grid-2">
            <div class="field">
                <label for="book_title">Book title <span class="req">*</span></label>
                <input id="book_title" name="book_title" type="text" required
                       class="input-field @error('book_title') is-invalid @enderror"
                       value="{{ old('book_title', $reservation->book_title) }}"
                       placeholder="e.g. Database System Concepts">
                @error('book_title') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="pickup_date">Pickup date <span class="req">*</span></label>
                <input id="pickup_date" name="pickup_date" type="date" required
                       class="input-field @error('pickup_date') is-invalid @enderror"
                       value="{{ old('pickup_date', $reservation->pickup_date?->format('Y-m-d')) }}">
                @error('pickup_date') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('reservations.index') }}" class="btn btn-outline btn-sm">Cancel</a>
        <button type="submit" class="btn btn-primary btn-sm glow-orange">
            {{ $submitLabel }}
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
        </button>
    </div>
</form>
