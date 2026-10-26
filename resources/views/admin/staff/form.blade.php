@extends('layouts.app')
@section('title', $staff->exists ? 'Edit staff' : 'Add staff')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">{{ $staff->exists ? 'Edit staff account' : 'Add staff account' }}</h3>
<form method="POST" action="{{ $staff->exists ? route('admin.staff.update', $staff) : route('admin.staff.store') }}" class="card card-body shadow-sm" style="max-width: 640px">
    @csrf @if($staff->exists) @method('PUT') @endif
    <div class="mb-3"><label class="form-label">Name *</label><input name="name" value="{{ old('name', $staff->name) }}" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" value="{{ old('email', $staff->email) }}" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone', $staff->phone) }}" class="form-control"></div>
    <div class="row g-3 mb-3">
        <div class="col"><label class="form-label">Role *</label><select name="role" class="form-select">
            <option value="librarian" @selected(old('role', $staff->role) === 'librarian')>Librarian</option>
            <option value="admin" @selected(old('role', $staff->role) === 'admin')>Administrator (head librarian)</option></select></div>
        @if($staff->exists)
            <div class="col"><label class="form-label">Status</label><select name="status" class="form-select">
                <option value="active" @selected($staff->status === 'active')>Active</option><option value="suspended" @selected($staff->status === 'suspended')>Suspended</option></select></div>
        @endif
    </div>
    <div class="mb-3"><label class="form-label">{{ $staff->exists ? 'New password (leave blank to keep)' : 'Password *' }}</label><input type="password" name="password" class="form-control" autocomplete="new-password" {{ $staff->exists ? '' : 'required' }}></div>
    <div><button class="btn btn-lib">Save</button> <a href="{{ route('admin.staff.index') }}" class="btn btn-link">Cancel</a></div>
</form>
@endsection
