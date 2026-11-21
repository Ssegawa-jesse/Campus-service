@extends('layouts.app')
@section('title', 'Travel updates')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1><i class="bi bi-broadcast"></i> Travel updates</h1><a href="{{ route('admin.travel-updates.create') }}" class="btn btn-gold"><i class="bi bi-plus-lg"></i> New update</a></div>
<p class="text-muted small">Ferry schedules at Paraa, road and gate status, seasonal weather advisories and UWA conservation notices. Active updates appear on the home page, the Travel Updates page, inside generated itineraries and in the AI assistant's knowledge.</p>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Update</th><th>Category</th><th>Severity</th><th>Validity</th><th>Status</th><th></th></tr></thead>
    @forelse ($updates as $u)
        <tr>
            <td>@if ($u->is_pinned)<i class="bi bi-pin-angle-fill text-gold"></i> @endif<strong>{{ $u->title }}</strong><div class="small text-muted">{{ Str::limit($u->body, 90) }}</div></td>
            <td class="small"><i class="bi bi-{{ $u->icon() }}"></i> {{ $u->categoryLabel() }}</td>
            <td><span class="badge bg-{{ $u->color() }}">{{ $u->severity }}</span></td>
            <td class="small">{{ $u->valid_from?->format('j M') ?? '—' }} → {{ $u->valid_until?->format('j M Y') ?? 'open' }}</td>
            <td>{!! $u->is_active ? '<span class="badge bg-success">Live</span>' : '<span class="badge bg-secondary">Hidden</span>' !!}</td>
            <td class="text-nowrap"><a href="{{ route('admin.travel-updates.edit', $u) }}" class="btn btn-sm btn-outline-forest">Edit</a>
                <form method="POST" action="{{ route('admin.travel-updates.destroy', $u) }}" class="d-inline" data-confirm="Delete this update?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No updates yet.</td></tr>
    @endforelse
</table></div></div>
@endsection
