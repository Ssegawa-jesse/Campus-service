@extends('layouts.app')
@section('title', 'My profile')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Account settings</h1></div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="row g-3">
            @csrf @method('PUT')
            <div class="col-12 d-flex align-items-center gap-3">
                <img src="{{ media_url($user->avatar, 'images/avatar.svg') }}" width="72" height="72" class="rounded-circle object-fit-cover" alt="">
                <div><label class="form-label mb-1">Profile photo</label><input type="file" name="avatar" accept="image/*" class="form-control form-control-sm"></div>
            </div>
            <div class="col-md-6"><label class="form-label">Full name</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Country</label><input name="country" value="{{ old('country', $user->country) }}" class="form-control"></div>
            <div class="col-12 form-check form-switch ms-2">
                <input type="hidden" name="sms_opt_in" value="0">
                <input class="form-check-input" type="checkbox" name="sms_opt_in" value="1" id="sms" @checked($user->sms_opt_in)>
                <label class="form-check-label" for="sms">Send me SMS notifications about my bookings and payments</label>
            </div>
            <div class="col-12"><hr><h6>Change password <small class="text-muted fw-normal">(leave blank to keep current)</small></h6></div>
            <div class="col-md-4"><label class="form-label">Current password</label><input type="password" name="current_password" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">New password</label><input type="password" name="password" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Confirm new password</label><input type="password" name="password_confirmation" class="form-control"></div>
            <div class="col-12"><button class="btn btn-forest">Save changes</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body p-4">
        <h5 class="mb-1"><i class="bi bi-clock-history text-gold"></i> Recent sign-in activity</h5>
        <p class="small text-muted">If you see a login you don't recognise, change your password above.</p>
        <table class="table table-sm small mb-0">
            @forelse ($activities as $a)
                <tr><td>{{ $a->created_at->format('d M Y H:i') }}</td><td><span class="badge bg-{{ $a->color() }}">{{ $a->label() }}</span></td><td class="text-muted">{{ $a->ip }}</td><td class="text-muted">{{ Str::limit($a->user_agent, 50) }}</td></tr>
            @empty
                <tr><td class="text-muted">No activity recorded yet.</td></tr>
            @endforelse
        </table>
    </div>
</div>
@endsection
