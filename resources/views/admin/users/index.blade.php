@extends('layouts.app')
@section('title', 'Users & admins')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>Users & admins</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-gold text-nowrap"><i class="bi bi-plus-lg"></i> Add user / admin</a>
</div>
<p class="text-muted small">Every registration is saved here. You can have as many administrators as you need: change a user's role to <strong>admin</strong>, or add a new admin. The last remaining admin cannot be demoted, suspended or deleted.</p>

<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
    <ul class="nav nav-pills gap-1">
        @foreach (['' => 'All', 'admin' => 'Admins', 'operator' => 'Operators', 'tourist' => 'Tourists'] as $key => $label)
            <li class="nav-item"><a class="nav-link {{ request('role', '') === $key ? 'active bg-forest' : 'text-forest' }}" href="{{ route('admin.users.index', array_filter(['role' => $key, 'q' => request('q')])) }}">{{ $label }} <span class="badge bg-secondary">{{ $key ? ($counts[$key] ?? 0) : $counts->sum() }}</span></a></li>
        @endforeach
    </ul>
    <form class="d-flex gap-2">
        <input type="hidden" name="role" value="{{ request('role') }}">
        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name, email, phone">
        <button class="btn btn-sm btn-outline-forest"><i class="bi bi-search"></i></button>
    </form>
</div>

<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table align-middle mb-0 small">
        <thead><tr><th style="min-width:170px">Name</th><th>Email / phone</th><th>Role</th><th class="text-nowrap">Last login</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
            @forelse ($users as $u)
                <tr>
                    <td>{{ $u->name }} @if ($u->id === auth()->id())<span class="badge bg-light text-dark">you</span>@endif
                        @unless ($u->is_active)<span class="badge bg-danger">Suspended</span>@endunless
                        @if ($u->operator)<div class="small text-muted">{{ $u->operator->company_name }} · <span class="text-{{ $u->operator->statusColor() }}">{{ $u->operator->statusLabel() }}</span></div>@endif</td>
                    <td class="small">{{ $u->email }}<br>{{ $u->phone }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.role', $u) }}" class="d-flex gap-1">@csrf @method('PUT')
                            <select name="role" class="form-select form-select-sm" style="width:115px" onchange="this.form.submit()" @disabled($u->id === auth()->id())>
                                @foreach (['tourist', 'operator', 'admin'] as $r)<option value="{{ $r }}" @selected($u->role === $r)>{{ ucfirst($r) }}</option>@endforeach
                            </select>
                        </form>
                    </td>
                    <td class="text-nowrap">{{ $u->last_login_at?->diffForHumans(short: true) ?? 'never' }}<div class="text-muted">joined {{ $u->created_at->format('d M Y') }}</div></td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#pw-{{ $u->id }}" title="Set a new password"><i class="bi bi-key"></i></button>
                        @if ($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary" title="{{ $u->is_active ? 'Suspend' : 'Activate' }}"><i class="bi bi-{{ $u->is_active ? 'pause-circle' : 'play-circle' }}"></i></button></form>
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="d-inline" data-confirm="Permanently delete {{ $u->name }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        @endif
                        <form method="POST" action="{{ route('admin.users.password', $u) }}" class="collapse mt-2" id="pw-{{ $u->id }}">@csrf @method('PUT')
                            <div class="input-group input-group-sm"><input name="password" class="form-control" placeholder="New password (min 8)" minlength="8" required autocomplete="new-password"><button class="btn btn-forest">Save</button></div>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
