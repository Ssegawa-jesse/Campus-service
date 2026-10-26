@extends('layouts.app')
@section('title', 'Welcome')
@section('content')
<div class="p-4 p-md-5 mb-4 rounded-3 text-white bg-lib shadow">
    <h1 class="display-6 fw-bold">Find, reserve and borrow books — from your phone</h1>
    <p class="lead mb-4">Search the {{ \App\Support\LibrarySettings::get('library_name') }} catalogue, check availability and shelf location, download past papers and recommend books the library should get.</p>
    <form action="{{ route('catalog.index') }}" class="row g-2">
        <div class="col-md-9"><input type="search" name="q" class="form-control form-control-lg" placeholder="Search by title, author, ISBN, subject or keyword"></div>
        <div class="col-md-3 d-grid"><button class="btn btn-light btn-lg"><i class="bi bi-search"></i> Search</button></div>
    </form>
    @guest
        <p class="mt-3 mb-0">New here? <a href="{{ route('register') }}" class="link-light fw-semibold">Register for a library account</a> to reserve books, renew loans and pay fines with Mobile Money.</p>
    @endguest
</div>

<div class="row g-3 mb-4 text-center">
    @foreach(['titles' => ['Titles', 'book'], 'copies' => ['Copies', 'stack'], 'available' => ['On the shelf now', 'check2-circle'], 'members' => ['Members', 'people'], 'resources' => ['Past papers & e-resources', 'file-earmark-pdf']] as $key => [$label, $icon])
        <div class="col-6 col-md"><div class="card stat-card h-100"><div class="card-body">
            <i class="bi bi-{{ $icon }} text-primary fs-3"></i>
            <div class="display-6">{{ number_format($stats[$key]) }}</div>
            <div class="text-muted small">{{ $label }}</div>
        </div></div></div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="mb-0">Recently added</h4>
            <a href="{{ route('catalog.index') }}">Browse catalogue &rarr;</a>
        </div>
        <div class="row g-3">
            @forelse($latest as $book)
                <div class="col-md-6">@include('partials.book-card', ['book' => $book])</div>
            @empty
                <p class="text-muted">No books in the catalogue yet.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-lightbulb text-warning"></i> Most wanted books</strong>
                <a href="{{ route('suggestions.index') }}" class="small">See all</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse($topSuggestions as $s)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $s->title }}<br><small class="text-muted">{{ $s->author }}</small></span>
                        <span class="badge bg-primary align-self-center">{{ $s->votes_count }} <i class="bi bi-hand-thumbs-up"></i></span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No recommendations yet.</li>
                @endforelse
            </ul>
            <div class="card-body">
                <p class="small text-muted mb-2">Can't find a book or it's never available? Tell the library.</p>
                <a href="{{ route('suggestions.create') }}" class="btn btn-warning w-100"><i class="bi bi-plus-circle"></i> Recommend a book</a>
            </div>
        </div>
    </div>
</div>
@endsection
