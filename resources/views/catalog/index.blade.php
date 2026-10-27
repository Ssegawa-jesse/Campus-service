@extends('layouts.app')
@section('title', 'Catalogue')
@section('content')
<section class="sl-hero p-4 mb-4">
    <h3 class="mb-1"><i class="bi bi-search"></i> Library catalogue</h3>
    <p class="mb-3 opacity-75">Search {{ number_format(\App\Models\Book::count()) }} titles by title, author, ISBN, subject or keyword.</p>
    <form class="hero-search row g-2 mx-0">
        <div class="col-md-6"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="What are you looking for?"></div>
        <div class="col-md-3">
            <select name="category" class="form-select border-0">
                <option value="">All categories</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="availability" class="form-select border-0">
                <option value="">Any availability</option>
                <option value="available" @selected(request('availability') === 'available')>On the shelf</option>
                <option value="unavailable" @selected(request('availability') === 'unavailable')>Not available</option>
            </select>
        </div>
        <div class="col-md-1 d-grid"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div>
    </form>
</section>

@if($books->isEmpty())
    <div class="card text-center py-5 px-3">
        <div class="icon-bubble tone-amber mx-auto mb-3" style="width:72px;height:72px;font-size:2rem"><i class="bi bi-search"></i></div>
        <h5>No books matched your search</h5>
        <p class="text-muted">If the library doesn't have the book you need, recommend it — others can support your request.</p>
        <div><a href="{{ route('suggestions.create', ['title' => request('q')]) }}" class="btn btn-warning px-4"><i class="bi bi-lightbulb"></i> Recommend "{{ request('q') ?: 'a book' }}"</a></div>
    </div>
@else
    <p class="text-muted small">{{ $books->total() }} {{ Str::plural('title', $books->total()) }} found</p>
    <div class="row g-4">
        @foreach($books as $book)
            <div class="col-6 col-sm-4 col-md-3 col-xl-2">@include('partials.book-card', ['book' => $book])</div>
        @endforeach
    </div>
    <div class="mt-4">{{ $books->links('pagination::bootstrap-5') }}</div>
@endif
@endsection
