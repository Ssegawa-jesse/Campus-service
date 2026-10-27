@extends('layouts.app')
@section('title', 'Choose a new password')
@section('content')
<div class="auth-shell row g-0">
    @include('auth._side', ['heading' => 'Almost there — choose a new password'])
    <div class="col-lg-7 p-4 p-md-5">
        <div class="icon-bubble tone-emerald mb-3"><i class="bi bi-shield-lock"></i></div>
        <h3 class="mb-1">Choose a new password</h3>
        <p class="text-muted mb-4">Use at least 8 characters.</p>
        <form method="POST" action="{{ route('password.update') }}">@csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $email) }}" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">New password</label><input type="password" name="password" class="form-control" required minlength="8" autofocus></div>
            <div class="mb-4"><label class="form-label">Confirm new password</label><input type="password" name="password_confirmation" class="form-control" required></div>
            <button class="btn btn-primary w-100 py-2">Save new password</button>
        </form>
    </div>
</div>
@endsection
