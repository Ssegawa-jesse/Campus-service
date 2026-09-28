@extends('layouts.app')
@section('title', 'Log in')
@section('no_footer', true)
@section('no_flash', true)

@section('content')
<div class="auth-wrap" style="--auth-img:url('{{ asset('uploads/seed/bwindi-impenetrable-national-park/cover.jpg') }}')">
    <div class="auth-art">
        <h2 class="fw-bold">Welcome back to the Pearl of Africa.</h2>
        <p class="text-white-50">Pick up where you left off – your saved parks, trip plans and bookings are waiting.</p>
    </div>
    <div class="auth-form">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h3 class="fw-bold mb-1">Log in</h3>
                <p class="text-muted small mb-4">Tourists, tour operators and administrators all log in here.</p>
                @include('partials.flash')
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus></div>
                    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control form-control-lg" required></div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label" for="remember">Remember me</label></div>
                    <button class="btn btn-forest btn-lg w-100">Log in</button>
                </form>
                <p class="text-center small mt-4 mb-0">New here? <a href="{{ route('register') }}">Create an account</a></p>
                @if (app()->environment('local'))
                    <div class="alert alert-light small mt-4 mb-0">
                        <strong>Demo accounts</strong> (password <code>Pearl360@2026</code>):<br>
                        Admin: {{ config('pearl360.admin_email') }}<br>Tourist: tourist@pearl360.test<br>Operator: operator@pearl360.test
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
