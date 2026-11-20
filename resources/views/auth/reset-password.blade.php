@extends('layouts.app')
@section('title', 'Choose a new password')

@section('content')
<div class="container py-5" style="max-width:520px">
    <div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
        <h3 class="fw-bold mb-4">Choose a new password</h3>
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $email) }}" class="form-control" required autocomplete="username"></div>
            <div class="mb-3"><label class="form-label">New password</label><input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
            <div class="mb-4"><label class="form-label">Confirm new password</label><input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
            <button class="btn btn-forest btn-lg w-100">Reset password</button>
        </form>
    </div></div>
</div>
@endsection
