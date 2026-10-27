@extends('layouts.app')
@section('title', 'Register')
@section('content')
<div class="auth-shell row g-0">
    @include('auth._side', ['heading' => 'Join the library in one minute'])
    <div class="col-lg-7 p-4 p-md-5">
        <h3 class="mb-1">Create your library account</h3>
        <p class="text-muted mb-4">Students and staff can register. Your details appear on your digital library card.</p>
        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">@csrf
            <div class="mb-4">@include('partials.photo-picker', ['user' => new \App\Models\User])</div>
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label">Full name *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                <div class="col-md-4">
                    <label class="form-label">I am a *</label>
                    <select name="type" class="form-select" required>
                        <option value="student" @selected(old('type') === 'student')>Student</option>
                        <option value="staff" @selected(old('type') === 'staff')>Staff member</option>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Phone (for SMS reminders) *</label><input name="phone" value="{{ old('phone') }}" class="form-control" placeholder="0772123456" required></div>
                @include('partials.member-fields', ['member' => new \App\Models\Member, 'required' => true])
                <div class="col-md-6">
                    <label class="form-label">Student / staff number</label>
                    <input name="member_no" value="{{ old('member_no') }}" class="form-control" placeholder="e.g. 2400720657">
                    <div class="form-text">Leave blank and we'll give you a library number.</div>
                </div>
                <div class="col-md-6"></div>
                <div class="col-md-6"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required minlength="8"></div>
                <div class="col-md-6"><label class="form-label">Confirm password *</label><input type="password" name="password_confirmation" class="form-control" required></div>
            </div>
            <button class="btn btn-primary w-100 py-2 mt-4"><i class="bi bi-person-check"></i> Create my account</button>
        </form>
        <p class="small text-center mt-4 mb-0">Already registered? <a href="{{ route('login') }}" class="fw-semibold">Log in</a></p>
    </div>
</div>
@endsection
