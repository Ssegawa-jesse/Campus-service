@extends('layouts.app')
@section('title', 'Forgot password')

@section('content')
<div class="container py-5" style="max-width:520px">
    <div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
        <h3 class="fw-bold mb-1">Forgot your password?</h3>
        <p class="text-muted small mb-4">Enter the email you registered with and we'll send you a link to choose a new password.</p>
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus autocomplete="username"></div>
            <button class="btn btn-forest btn-lg w-100">Send reset link</button>
        </form>
        <p class="small text-muted mt-4 mb-0"><i class="bi bi-info-circle"></i> No email? Ask an administrator to set a new password for you from Admin → Users.</p>
        <p class="text-center small mt-3 mb-0"><a href="{{ route('login') }}">← Back to log in</a></p>
    </div></div>
</div>
@endsection
