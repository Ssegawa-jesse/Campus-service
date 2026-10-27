@extends('layouts.app')
@section('title', 'Forgot password')
@section('content')
<div class="auth-shell row g-0">
    @include('auth._side', ['heading' => "Forgot your password? It happens."])
    <div class="col-lg-7 p-4 p-md-5">
        <div class="icon-bubble tone-amber mb-3"><i class="bi bi-key"></i></div>
        <h3 class="mb-1">Reset your password</h3>
        <p class="text-muted mb-4">Enter the email address you registered with and we'll send you a link to choose a new password.</p>
        @if(session('demo_reset_link'))
            <div class="alert alert-warning small">
                <strong><i class="bi bi-info-circle"></i> Demo mode:</strong> email sending isn't set up on this computer, so here is the reset link that would have been emailed:
                <a href="{{ session('demo_reset_link') }}" class="d-block mt-2 btn btn-sm btn-warning">Open password reset link</a>
            </div>
        @endif
        <form method="POST" action="{{ route('password.email') }}">@csrf
            <div class="mb-4">
                <label class="form-label">Email</label>
                <div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus></div>
            </div>
            <button class="btn btn-primary w-100 py-2"><i class="bi bi-send"></i> Email me a reset link</button>
        </form>
        <p class="small text-center mt-4 mb-0"><a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Back to log in</a></p>
    </div>
</div>
@endsection
