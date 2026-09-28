@extends('layouts.app')
@section('title', 'Company profile')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Company profile</h1>@if ($operator->is_verified)<a href="{{ route('operators.show', $operator) }}" class="btn btn-outline-forest" target="_blank">View public page</a>@endif</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="{{ route('operator.profile.update') }}" enctype="multipart/form-data" class="row g-3">
        @csrf @method('PUT')
        <div class="col-12 d-flex gap-3 align-items-center">
            <img src="{{ $operator->logoUrl() }}" width="80" height="80" class="rounded object-fit-cover" alt="">
            <div><label class="form-label mb-1">Logo</label><input type="file" name="logo" accept="image/*" class="form-control form-control-sm"></div>
        </div>
        <div class="col-md-8"><label class="form-label">Company name</label><input name="company_name" value="{{ old('company_name', $operator->company_name) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Licence no.</label><input name="licence_no" value="{{ old('licence_no', $operator->licence_no) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Booking phone (SMS alerts)</label><input name="contact_phone" value="{{ old('contact_phone', $operator->contact_phone) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Booking email</label><input type="email" name="contact_email" value="{{ old('contact_email', $operator->contact_email) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Website</label><input name="website" value="{{ old('website', $operator->website) }}" class="form-control" placeholder="https://"></div>
        <div class="col-md-6"><label class="form-label">Office address</label><input name="address" value="{{ old('address', $operator->address) }}" class="form-control"></div>
        <div class="col-12"><label class="form-label">About your company</label><textarea name="about" rows="4" class="form-control">{{ old('about', $operator->about) }}</textarea></div>
        <div class="col-12">
            <label class="form-label">Parks you operate in</label>
            <div class="row g-1">
                @foreach ($parks as $p)
                    <div class="col-md-4 form-check"><input class="form-check-input" type="checkbox" name="parks[]" value="{{ $p->id }}" id="p{{ $p->id }}" @checked($operator->parks->contains($p->id))><label class="form-check-label small" for="p{{ $p->id }}">{{ $p->name }}</label></div>
                @endforeach
            </div>
        </div>
        <div class="col-12"><button class="btn btn-forest">Save profile</button></div>
    </form>
</div></div>
@endsection
