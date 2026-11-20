@extends('layouts.app')
@section('title', 'Create an account')
@section('no_footer', true)
@section('no_flash', true)

@section('content')
<div class="auth-wrap" style="--auth-img:url('{{ asset('uploads/seed/queen-elizabeth-national-park/cover.jpg') }}')">
    <div class="auth-art">
        <h2 class="fw-bold">Join Pearl360.</h2>
        <p class="text-white-50">Tourists: save parks, plan trips and book with Mobile Money.<br>Operators: receive enquiries from tourists who have already explored the parks in 360°.</p>
    </div>
    <div class="auth-form">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h3 class="fw-bold mb-3">Create your account</h3>
                @include('partials.flash')
                <form method="POST" action="{{ route('register') }}" class="row g-3">
                    @csrf
                    <div class="col-6 role-pick">
                        <input type="radio" name="role" value="tourist" id="rTourist" @checked(old('role', $role) === 'tourist')>
                        <label for="rTourist"><i class="bi bi-backpack2 fs-3 text-forest d-block"></i><strong>Tourist</strong><div class="small text-muted">Explore & book</div></label>
                    </div>
                    <div class="col-6 role-pick">
                        <input type="radio" name="role" value="operator" id="rOperator" @checked(old('role', $role) === 'operator')>
                        <label for="rOperator"><i class="bi bi-briefcase fs-3 text-gold d-block"></i><strong>Tour operator</strong><div class="small text-muted">Operator / lodge</div></label>
                    </div>
                    <div class="col-12"><label class="form-label">Full name</label><input name="name" value="{{ old('name') }}" class="form-control" required autocomplete="name"></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required autocomplete="username"></div>
                    <div class="col-md-6"><label class="form-label">Phone (for SMS)</label><input type="tel" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="0772 123456" required autocomplete="tel"></div>
                    <div id="operatorFields" class="col-12 row g-3 m-0 p-0">
                        <div class="col-md-7"><label class="form-label">Company name <span class="text-danger">*</span></label><input name="company_name" value="{{ old('company_name') }}" class="form-control" autocomplete="organization"></div>
                        <div class="col-12"><div class="alert alert-warning small py-2 mb-0"><i class="bi bi-hourglass-split"></i> Operator accounts are reviewed by a UWA administrator. You can log in straight away, and your packages go live once you're approved.</div></div>
                        <div class="col-md-5"><label class="form-label">Licence no. (UTB)</label><input name="licence_no" value="{{ old('licence_no') }}" class="form-control"></div>
                    </div>
                    <div class="col-12"><label class="form-label">Country</label><input name="country" value="{{ old('country', 'Uganda') }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Password</label><div class="input-group"><input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password" data-password><button class="btn btn-outline-secondary" type="button" data-toggle-password title="Show / hide"><i class="bi bi-eye"></i></button></div></div>
                    <div class="col-md-6"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password" data-password></div>
                    <div class="col-12"><button class="btn btn-forest btn-lg w-100">Create account</button></div>
                </form>
                <p class="text-center small mt-3 mb-0">Already registered? <a href="{{ route('login') }}">Log in</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
