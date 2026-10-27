@extends('layouts.app')
@section('title', $member->exists ? 'Edit member' : 'Register member')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<div class="page-title"><h3>{{ $member->exists ? 'Edit member' : 'Register member' }}</h3></div>
<form method="POST" action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}" enctype="multipart/form-data" class="card card-body p-4">
    @csrf @if($member->exists) @method('PUT') @endif
    <div class="mb-4">@include('partials.photo-picker', ['user' => $user])</div>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full name *</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Type *</label>
            <select name="type" class="form-select"><option value="student" @selected(old('type', $member->type) === 'student')>Student</option><option value="staff" @selected(old('type', $member->type) === 'staff')>Staff</option></select></div>
        <div class="col-md-3"><label class="form-label">Member number</label><input name="member_no" value="{{ old('member_no', $member->member_no) }}" class="form-control" placeholder="Auto if blank"></div>
        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Phone *</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control" required></div>
        @include('partials.member-fields', ['member' => $member])
        <div class="col-md-3"><label class="form-label">Borrowing limit</label><input type="number" name="max_loans" value="{{ old('max_loans', $member->max_loans) }}" class="form-control" placeholder="Default"></div>
        <div class="col-md-3"><label class="form-label">Membership expires</label><input type="date" name="expiry_date" value="{{ old('expiry_date', $member->expiry_date?->format('Y-m-d')) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">{{ $member->exists ? 'Reset password (leave blank to keep)' : 'Password (leave blank to generate)' }}</label><input type="text" name="password" class="form-control" autocomplete="new-password"></div>
    </div>
    <div class="mt-4"><button class="btn btn-primary px-4">Save</button> <a href="{{ route('admin.members.index') }}" class="btn btn-link">Cancel</a></div>
</form>
@endsection
