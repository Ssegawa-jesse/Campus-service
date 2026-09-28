@extends('layouts.app')
@section('title', 'Users')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>Users</h1>
    <div class="d-flex gap-2">
        <form class="d-flex gap-2">
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name, email, phone">
            <select name="role" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">All roles</option>@foreach (['admin', 'tourist', 'operator'] as $r)<option @selected(request('role') === $r)>{{ $r }}</option>@endforeach</select>
        </form>
        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-gold text-nowrap"><i class="bi bi-plus-lg"></i> Add user</a>
    </div>
</div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Name</th><th>Email / phone</th><th>Role</th><th>Country</th><th>Joined</th><th>Status</th><th></th></tr></thead>
        <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td class="small">{{ $u->email }}<br>{{ $u->phone }}</td>
                    <td><span class="badge role-badge role-{{ $u->role }} text-capitalize">{{ $u->role }}</span></td>
                    <td class="small">{{ $u->country }}</td>
                    <td class="small">{{ $u->created_at->format('d M Y') }}</td>
                    <td>{!! $u->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Suspended</span>' !!}</td>
                    <td class="text-nowrap">
                        @if ($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $u->is_active ? 'Suspend' : 'Activate' }}</button></form>
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="d-inline" data-confirm="Permanently delete {{ $u->name }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
