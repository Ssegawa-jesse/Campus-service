@extends('layouts.app')
@section('title', 'Catalogue')
@section('content')
<h3 class="mb-3"><i class="bi bi-search"></i> Library catalogue</h3>
<form class="card card-body shadow-sm mb-4">
    <div class="row g-2">
        <div class="col-md-6"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Title, author, ISBN, subject or keyword"></div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All categories</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="availability" class="form-select">
                <option value="">Any availability</option>
                <option value="available" @selected(request('availability') === 'available')>On the shelf</option>
                <option value="unavailable" @selected(request('availability') === 'unavailable')>Not available</option>
            </select>
        </div>
        <div class="col-md-1 d-grid"><button class="btn btn-lib"><i class="bi bi-search"></i></button></div>
    </div>
</form>

@if($books->isEmpty())
    <div class="text-center py-5">
        <i class="bi bi-emoji-frown fs-1 text-muted"></i>
        <h5 class="mt-2">No books matched your search.</h5>
        <p class="text-muted">If the library doesn't have the book you need, recommend it — others can support your request.</p>
        <a href="{{ route('suggestions.create', ['title' => request('q')]) }}" class="btn btn-warning"><i class="bi bi-lightbulb"></i> Recommend "{{ request('q') ?: 'a book' }}"</a>
    </div>
@else
    <p class="text-muted small">{{ $books->total() }} {{ Str::plural('title', $books->total()) }} found</p>
    <div class="row g-3">
        @foreach($books as $book)
            <div class="col-md-6 col-xl-4">@include('partials.book-card', ['book' => $book])</div>
        @endforeach
    </div>
    <div class="mt-4">{{ $books->links('pagination::bootstrap-5') }}</div>
@endif
@endsection
