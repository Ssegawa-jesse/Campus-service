@extends('layouts.app')
@section('title', 'Audit log')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">Audit log</h3>
<form class="mb-3"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search actions or details"></form>
<div class="card shadow-sm">
    <table class="table table-sm mb-0">
        <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr><td class="text-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td><td>{{ $log->user?->name ?? 'System' }}</td><td><code>{{ $log->action }}</code></td><td>{{ $log->details }}</td><td>{{ $log->ip_address }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-muted text-center py-4">No entries.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $logs->links('pagination::bootstrap-5') }}</div>
@endsection
