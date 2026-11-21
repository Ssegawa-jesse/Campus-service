@extends('layouts.app')
@section('title', 'AI assistant logs')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1><i class="bi bi-robot"></i> AI Travel Assistant logs</h1></div>
<div class="d-flex flex-wrap gap-2 mb-3">@foreach ($intents as $intent => $n)<span class="badge bg-forest-light text-forest">{{ $intent ?: 'other' }}: {{ $n }}</span>@endforeach</div>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-sm small align-top mb-0">
    <thead><tr><th>When</th><th>Visitor</th><th>Question</th><th>Answer</th><th>Source</th></tr></thead>
    @forelse ($logs as $l)
        <tr><td class="text-nowrap">{{ $l->created_at->format('d M H:i') }}</td><td>{{ $l->user?->name ?? 'Guest' }}</td><td>{{ $l->question }}</td><td>{{ Str::limit($l->answer, 220) }}</td><td><span class="badge bg-{{ $l->source === 'claude' ? 'success' : 'secondary' }}">{{ $l->source }}</span></td></tr>
    @empty<tr><td colspan="5" class="text-muted">No questions yet.</td></tr>@endforelse
</table></div></div>
<div class="mt-2">{{ $logs->links() }}</div>
@endsection
