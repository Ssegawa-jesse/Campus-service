@extends('layouts.app')
@section('title', 'Profile')
@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm"><div class="card-body">
            <h5>Profile</h5>
            <form method="POST" action="{{ route('profile.update') }}">@csrf @method('PUT')
                <div class="mb-3"><label class="form-label">Name</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control" required></div>
                @if($user->member)
                    <div class="mb-3"><label class="form-label">Programme / department</label><input name="programme" value="{{ old('programme', $user->member->programme) }}" class="form-control"></div>
                @endif
                <button class="btn btn-lib">Save</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm"><div class="card-body">
            <h5>Change password</h5>
            <form method="POST" action="{{ route('profile.password') }}">@csrf @method('PUT')
                <div class="mb-3"><label class="form-label">Current password</label><input type="password" name="current_password" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">New password</label><input type="password" name="password" class="form-control" required minlength="8"></div>
                <div class="mb-3"><label class="form-label">Confirm new password</label><input type="password" name="password_confirmation" class="form-control" required></div>
                <button class="btn btn-lib">Change password</button>
            </form>
        </div></div>
    </div>
</div>
@endsection
