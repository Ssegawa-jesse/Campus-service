@extends('layouts.app')
@section('title', 'Members')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Members</h3>
    <a href="{{ route('admin.members.create') }}" class="btn btn-lib"><i class="bi bi-person-plus"></i> Register member</a>
</div>
<form class="row g-2 mb-3">
    <div class="col-md-8"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, number, email or phone"></div>
    <div class="col-md-3"><select name="type" class="form-select"><option value="">All types</option><option value="student" @selected(request('type') === 'student')>Students</option><option value="staff" @selected(request('type') === 'staff')>Staff</option></select></div>
    <div class="col-md-1 d-grid"><button class="btn btn-lib"><i class="bi bi-search"></i></button></div>
</form>
<div class="card shadow-sm">
    <table class="table table-hover mb-0">
        <thead><tr><th>Number</th><th>Name</th><th>Type</th><th>Phone</th><th>Loans</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($members as $m)
            <tr>
                <td><code>{{ $m->member_no }}</code></td>
                <td><a href="{{ route('admin.members.show', $m) }}">{{ $m->user->name }}</a><br><small class="text-muted">{{ $m->user->email }}</small></td>
                <td>{{ ucfirst($m->type) }}</td><td>{{ $m->user->phone }}</td>
                <td>{{ $m->active_loans_count }} / {{ $m->loanLimit() }}</td>
                <td><span class="badge bg-{{ $m->user->isActive() ? 'success' : 'danger' }}">{{ ucfirst($m->user->status) }}</span>@if($m->isExpired()) <span class="badge bg-secondary">Expired</span>@endif</td>
                <td class="text-end"><a href="{{ route('admin.members.card', $m) }}" class="btn btn-sm btn-outline-primary" title="Library card"><i class="bi bi-person-vcard"></i></a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted text-center py-4">No members found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $members->links('pagination::bootstrap-5') }}</div>
@endsection
