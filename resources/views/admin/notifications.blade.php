@extends('layouts.app')
@section('title', 'SMS & email log')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">SMS &amp; email log</h3>
<div class="card shadow-sm">
    <table class="table table-sm mb-0">
        <thead><tr><th>When</th><th>To</th><th>Channel</th><th>Message</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($notifications as $n)
            <tr><td class="text-nowrap">{{ $n->sent_at?->format('d M Y H:i') }}</td><td>{{ $n->user->name }}<br><small class="text-muted">{{ $n->channel === 'sms' ? $n->user->phone : $n->user->email }}</small></td>
                <td>{{ strtoupper($n->channel) }}</td><td class="small">{{ $n->message }}</td>
                <td><span class="badge bg-{{ ['sent' => 'success', 'logged' => 'secondary', 'failed' => 'danger'][$n->status] ?? 'secondary' }}">{{ $n->status }}</span></td></tr>
        @empty
            <tr><td colspan="5" class="text-muted text-center py-4">No messages sent yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $notifications->links('pagination::bootstrap-5') }}</div>
@endsection
