@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<div class="auth-shell row g-0">
    @include('auth._side', ['heading' => 'Welcome back to your library'])
    <div class="col-lg-7 p-4 p-md-5">
        <h3 class="mb-1">Log in</h3>
        <p class="text-muted mb-4">Use the email and password you registered with.</p>
        <form method="POST" action="{{ route('login') }}">@csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus></div>
            </div>
            <div class="mb-2">
                <div class="d-flex justify-content-between"><label class="form-label">Password</label><a href="{{ route('password.request') }}" class="small">Forgot password?</a></div>
                <div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" required></div>
            </div>
            <div class="form-check mb-4">
                <input type="checkbox" name="remember" id="remember" class="form-check-input">
                <label for="remember" class="form-check-label small">Keep me logged in</label>
            </div>
            <button class="btn btn-primary w-100 py-2">Log in</button>
        </form>
        <p class="small text-center mt-4 mb-0">New to the library? <a href="{{ route('register') }}" class="fw-semibold">Create an account</a></p>
    </div>
</div>
@endsection
