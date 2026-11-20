@extends('layouts.app')
@section('title', 'Add user')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Add user</h1></div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="{{ route('admin.users.store') }}" class="row g-3">
        @csrf
        <div class="col-md-6"><label class="form-label">Full name</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Role</label>
            <select name="role" class="form-select" id="roleSel">
                <option value="tourist" @selected(old('role') === 'tourist')>Tourist</option>
                <option value="operator" @selected(old('role') === 'operator')>Tour operator (auto-verified)</option>
                <option value="admin" @selected(old('role') === 'admin')>Administrator (UWA staff)</option>
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone') }}" class="form-control" required></div>
        <div class="col-md-6" id="companyWrap"><label class="form-label">Company name</label><input name="company_name" value="{{ old('company_name') }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Temporary password</label><input name="password" class="form-control" required minlength="8"></div>
        <div class="col-12"><button class="btn btn-forest">Create user</button></div>
    </form>
</div></div>
@endsection

@push('scripts')
<script>const rs = document.getElementById('roleSel'), cw = document.getElementById('companyWrap'); const f = () => cw.classList.toggle('d-none', rs.value !== 'operator'); rs.addEventListener('change', f); f();</script>
@endpush
