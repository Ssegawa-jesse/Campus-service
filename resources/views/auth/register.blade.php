@extends('layouts.app')
@section('title', 'Register')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-1"><i class="bi bi-person-plus"></i> Create your library account</h4>
                <p class="text-muted small">Students and staff can register to reserve and renew books, receive SMS reminders, pay fines with Mobile Money and recommend new books.</p>
                <form method="POST" action="{{ route('register') }}">@csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full name *</label>
                            <input name="name" value="{{ old('name') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">I am a *</label>
                            <select name="type" class="form-select" required>
                                <option value="student" @selected(old('type') === 'student')>Student</option>
                                <option value="staff" @selected(old('type') === 'staff')>Staff member</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone (for SMS reminders) *</label>
                            <input name="phone" value="{{ old('phone') }}" class="form-control" placeholder="0772123456" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Student / staff number</label>
                            <input name="member_no" value="{{ old('member_no') }}" class="form-control" placeholder="e.g. 2400720657">
                            <div class="form-text">Leave blank and we will give you a library number.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Programme / department</label>
                            <input name="programme" value="{{ old('programme') }}" class="form-control" placeholder="e.g. Bachelor of Business Computing">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm password *</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <button class="btn btn-lib w-100 mt-4">Register</button>
                </form>
                <p class="small text-center mt-3 mb-0">Already registered? <a href="{{ route('login') }}">Log in</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
