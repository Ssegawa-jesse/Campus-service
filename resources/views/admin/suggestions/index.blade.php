@extends('layouts.app')
@section('title', 'Recommendations')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">Book recommendations</h3>
<p class="text-muted">Recommendations from members, sorted by how many people need each book. Updating a status emails the member who recommended it.</p>
@php($status = request('status', 'pending'))
<ul class="nav nav-pills mb-3">
    @foreach(['pending' => 'Pending'] + array_diff_key(\App\Models\BookSuggestion::STATUSES, ['pending' => 1]) + ['all' => 'All'] as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.suggestions.index', ['status' => $key]) }}">{{ $label }}</a></li>
    @endforeach
</ul>
@forelse($suggestions as $s)
    <div class="card shadow-sm mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <h5 class="mb-1">{{ $s->title }} <span class="badge bg-primary">{{ $s->votes_count }} <i class="bi bi-hand-thumbs-up"></i></span></h5>
                <div class="small text-muted mb-1">{{ $s->author }}@if($s->isbn) · ISBN {{ $s->isbn }}@endif @if($s->course) · {{ $s->course }}@endif</div>
                <div class="small">By {{ $s->user->name }} on {{ $s->created_at->format('d M Y') }}</div>
                @if($s->book)<div class="small">Existing title: <a href="{{ route('admin.copies.index', $s->book) }}">{{ $s->book->title }}</a> — add copies</div>@endif
                @if($s->reason)<p class="mb-0 mt-2">{{ $s->reason }}</p>@endif
            </div>
            <div class="col-md-6">
                <form method="POST" action="{{ route('admin.suggestions.update', $s) }}">@csrf @method('PUT')
                    <div class="row g-2">
                        <div class="col-5"><select name="status" class="form-select form-select-sm">@foreach(\App\Models\BookSuggestion::STATUSES as $k => $l)<option value="{{ $k }}" @selected($s->status === $k)>{{ $l }}</option>@endforeach</select></div>
                        <div class="col-7"><button class="btn btn-sm btn-lib w-100">Update</button></div>
                        <div class="col-12"><textarea name="response" rows="2" class="form-control form-control-sm" placeholder="Response to the member (public)">{{ $s->response }}</textarea></div>
                    </div>
                </form>
                @if($s->responder)<small class="text-muted">Last updated by {{ $s->responder->name }}</small>@endif
                <form method="POST" action="{{ route('suggestions.destroy', $s) }}" class="mt-1" onsubmit="return confirm('Delete this recommendation?')">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0">Delete (spam / duplicate)</button></form>
            </div>
        </div>
    </div></div>
@empty
    <p class="text-muted">No recommendations here.</p>
@endforelse
{{ $suggestions->links('pagination::bootstrap-5') }}
@endsection
