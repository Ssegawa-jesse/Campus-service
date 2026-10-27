@extends('layouts.app')
@section('title', 'Profile')
@section('content')
<section class="sl-hero p-4 mb-4 d-flex flex-wrap align-items-center gap-3">
    @include('partials.avatar', ['user' => $user, 'size' => 76, 'class' => 'avatar-ring'])
    <div>
        <h3 class="mb-0">{{ $user->name }}</h3>
        <div class="opacity-75">{{ $user->roleLabel() }}@if($user->member) · {{ $user->member->member_no }}@endif</div>
    </div>
    @if($user->member)<a href="{{ route('my.card') }}" class="btn btn-light-glass ms-md-auto"><i class="bi bi-person-vcard"></i> View my library card</a>@endif
</section>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card"><div class="card-body p-4">
            <h5 class="mb-3">Personal details</h5>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
                <div class="mb-3">@include('partials.photo-picker', ['user' => $user])</div>
                @if($user->photo)
                    <div class="mb-3 small"><button form="remove-photo" class="btn btn-link btn-sm text-danger p-0"><i class="bi bi-trash"></i> Remove current photo</button></div>
                @endif
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Full name</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
                    @if($user->member)
                        <input type="hidden" name="type" value="{{ $user->member->type }}">
                        @include('partials.member-fields', ['member' => $user->member])
                    @endif
                </div>
                <button class="btn btn-primary mt-4 px-4">Save changes</button>
            </form>
            <form method="POST" action="{{ route('profile.photo.destroy') }}" id="remove-photo" onsubmit="return confirm('Remove your profile picture?')">@csrf @method('DELETE')</form>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-body p-4">
            <h5 class="mb-3"><i class="bi bi-shield-lock text-primary"></i> Change password</h5>
            <form method="POST" action="{{ route('profile.password') }}">@csrf @method('PUT')
                <div class="mb-3"><label class="form-label">Current password</label><input type="password" name="current_password" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">New password</label><input type="password" name="password" class="form-control" required minlength="8"></div>
                <div class="mb-3"><label class="form-label">Confirm new password</label><input type="password" name="password_confirmation" class="form-control" required></div>
                <button class="btn btn-outline-primary">Change password</button>
            </form>
        </div></div>
    </div>
</div>
@endsection
