@extends('layouts.app')
@section('title', 'Login activity')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>Login activity</h1>
    <form class="d-flex gap-2">
        <select name="event" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All events</option>
            @foreach (\App\Models\LoginActivity::EVENTS as $k => [$label])<option value="{{ $k }}" @selected(request('event') === $k)>{{ $label }}</option>@endforeach
        </select>
        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Email">
    </form>
</div>
<p class="text-muted small"><i class="bi bi-shield-lock"></i> Every registration, login, failed login and logout is recorded with time and IP address. Passwords are stored only as secure bcrypt hashes and are never visible, even to admins. Use the <i class="bi bi-key"></i> button on the Users page to set a new password for someone.</p>
<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table table-sm align-middle mb-0 small">
        <thead><tr><th>When</th><th>Event</th><th>User</th><th>Email used</th><th>IP address</th><th>Device / browser</th></tr></thead>
        <tbody>
            @forelse ($activities as $a)
                <tr>
                    <td class="text-nowrap">{{ $a->created_at->format('d M Y H:i') }}<div class="text-muted">{{ $a->created_at->diffForHumans() }}</div></td>
                    <td><span class="badge bg-{{ $a->color() }}">{{ $a->label() }}</span></td>
                    <td>{{ $a->user?->name ?? '–' }}@if ($a->user)<div class="text-muted text-capitalize">{{ $a->user->role }}</div>@endif</td>
                    <td>{{ $a->email }}</td>
                    <td>{{ $a->ip }}</td>
                    <td class="text-muted" style="max-width:280px">{{ Str::limit($a->user_agent, 70) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No activity yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $activities->links() }}</div>
@endsection
