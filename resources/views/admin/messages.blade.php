@extends('layouts.app')
@section('title', 'Contact messages')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Contact messages</h1></div>
@forelse ($messages as $m)
    <div class="card border-0 shadow-sm mb-2 {{ $m->is_read ? '' : 'border-start border-4 border-warning' }}"><div class="card-body">
        <div class="d-flex justify-content-between"><strong>{{ $m->subject }}</strong><small class="text-muted">{{ $m->created_at->format('d M Y H:i') }}</small></div>
        <div class="small text-muted mb-2">{{ $m->name }} · <a href="mailto:{{ $m->email }}">{{ $m->email }}</a> {{ $m->phone ? '· '.$m->phone : '' }}</div>
        <p class="mb-0">{!! nl2br(e($m->message)) !!}</p>
    </div></div>
@empty
    <p class="text-muted">No messages.</p>
@endforelse
<div class="mt-3">{{ $messages->links() }}</div>
@endsection
