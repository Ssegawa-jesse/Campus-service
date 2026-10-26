@extends('layouts.app')
@section('title', 'Book recommendations')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h3 class="mb-0"><i class="bi bi-lightbulb text-warning"></i> Book recommendations</h3>
        <p class="text-muted mb-0">Books students and staff want the library to buy. Support the ones you need too — the most supported are considered first.</p>
    </div>
    <a href="{{ route('suggestions.create') }}" class="btn btn-warning"><i class="bi bi-plus-circle"></i> Recommend a book</a>
</div>
<form class="row g-2 mb-3">
    <div class="col-md-6"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search title, author or course"></div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(\App\Models\BookSuggestion::STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="sort" class="form-select">
            <option value="">Most supported</option>
            <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
        </select>
    </div>
    <div class="col-md-1 d-grid"><button class="btn btn-lib"><i class="bi bi-funnel"></i></button></div>
</form>

<div class="row g-3">
@forelse($suggestions as $s)
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body d-flex gap-3">
                <div class="text-center" style="min-width:70px">
                    @auth
                        <form method="POST" action="{{ route('suggestions.vote', $s) }}">@csrf
                            @php($voted = in_array($s->id, $votedIds))
                            <button class="btn btn-sm {{ $voted ? 'btn-primary' : 'btn-outline-primary' }}" title="{{ $voted ? 'Remove my support' : 'I need this too' }}">
                                <i class="bi bi-hand-thumbs-up{{ $voted ? '-fill' : '' }}"></i>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary" title="Log in to support"><i class="bi bi-hand-thumbs-up"></i></a>
                    @endauth
                    <div class="fw-bold fs-5">{{ $s->votes_count }}</div>
                    <div class="small text-muted">{{ Str::plural('vote', $s->votes_count) }}</div>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between flex-wrap">
                        <h5 class="mb-1">{{ $s->title }}</h5>
                        <span class="badge bg-{{ $s->statusColour() }} align-self-start">{{ $s->statusLabel() }}</span>
                    </div>
                    <div class="small text-muted mb-2">
                        @if($s->author){{ $s->author }} · @endif
                        @if($s->isbn)ISBN {{ $s->isbn }} · @endif
                        @if($s->course)For {{ $s->course }} · @endif
                        Recommended by {{ $s->user->name }} {{ $s->created_at->diffForHumans() }}
                    </div>
                    @if($s->book)
                        <div class="small mb-1"><i class="bi bi-info-circle"></i> In the catalogue but often unavailable: <a href="{{ route('catalog.show', $s->book) }}">{{ $s->book->title }}</a></div>
                    @endif
                    @if($s->reason)<p class="mb-1">{{ $s->reason }}</p>@endif
                    @if($s->response)<div class="alert alert-light border small mb-0 mt-2"><strong>Library response:</strong> {{ $s->response }}</div>@endif
                    @auth
                        @if($s->user_id === auth()->id() && $s->status === 'pending')
                            <form method="POST" action="{{ route('suggestions.destroy', $s) }}" class="mt-2" onsubmit="return confirm('Withdraw this recommendation?')">@csrf @method('DELETE')
                                <button class="btn btn-link btn-sm text-danger p-0">Withdraw</button>
                            </form>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12 text-center text-muted py-5">No recommendations yet. Be the first to recommend a book!</div>
@endforelse
</div>
<div class="mt-3">{{ $suggestions->links('pagination::bootstrap-5') }}</div>
@endsection
