@extends('layouts.app')
@section('title', 'Welcome')
@section('content')
<section class="sl-hero p-4 p-md-5 mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="badge rounded-pill text-bg-light text-primary mb-3 px-3 py-2"><i class="bi bi-stars"></i> Makerere University Business School</span>
            <h1 class="display-5 fw-bold mb-3">Find, reserve and borrow books — right from your phone</h1>
            <p class="lead mb-4">Search the {{ \App\Support\LibrarySettings::get('library_name') }} catalogue, see what's on the shelf, download past papers and recommend the books you need.</p>
            <form action="{{ route('catalog.index') }}" class="hero-search d-flex gap-2">
                <span class="d-flex align-items-center ps-2 text-muted"><i class="bi bi-search"></i></span>
                <input type="search" name="q" class="form-control" placeholder="Title, author, ISBN or subject…" aria-label="Search the catalogue">
                <button class="btn btn-primary px-4">Search</button>
            </form>
            <div class="d-flex flex-wrap gap-2 mt-4">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-warning px-4"><i class="bi bi-person-plus"></i> Create free account</a>
                    <a href="{{ route('login') }}" class="btn btn-light-glass px-4">Log in</a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-warning px-4"><i class="bi bi-grid-1x2"></i> Go to my dashboard</a>
                @endguest
            </div>
        </div>
        <div class="col-lg-5 d-none d-lg-block">
            <div class="hero-covers">
                @foreach($heroBooks as $b)
                    <a href="{{ route('catalog.show', $b) }}"><img src="{{ $b->coverUrl() }}" alt="{{ $b->title }}"></a>
                @endforeach
            </div>
        </div>
    </div>
</section>

<div class="row g-3 mb-5">
    @foreach([
        ['titles', 'Book titles', 'book', 'tone-indigo'],
        ['copies', 'Copies in the library', 'stack', 'tone-violet'],
        ['available', 'On the shelf now', 'check2-circle', 'tone-emerald'],
        ['members', 'Registered members', 'people', 'tone-pink'],
        ['resources', 'Past papers & e-books', 'file-earmark-pdf', 'tone-amber'],
    ] as [$key, $label, $icon, $tone])
        <div class="col-6 col-md-4 col-xl"><div class="card stat-tile">
            <div class="icon {{ $tone }}"><i class="bi bi-{{ $icon }}"></i></div>
            <div><div class="value">{{ number_format($stats[$key]) }}</div><div class="label">{{ $label }}</div></div>
        </div></div>
    @endforeach
</div>

@if($categories->isNotEmpty())
    <h4 class="mb-3">Browse by category</h4>
    <div class="d-flex flex-wrap gap-2 mb-5">
        @foreach($categories as $c)
            <a href="{{ route('catalog.index', ['category' => $c->id]) }}" class="chip"><i class="bi bi-bookmark"></i> {{ $c->name }} <span class="badge soft-indigo">{{ $c->books_count }}</span></a>
        @endforeach
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h4 class="mb-0"><i class="bi bi-fire text-danger"></i> Popular right now</h4>
            <a href="{{ route('catalog.index') }}" class="fw-medium">Browse all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-3 mb-5">
            @foreach($popular as $book)
                <div class="col-6 col-sm-4 col-md-2 col-lg-4 col-xl-2">@include('partials.book-card', ['book' => $book])</div>
            @endforeach
        </div>
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h4 class="mb-0"><i class="bi bi-stars text-warning"></i> New arrivals</h4>
        </div>
        <div class="row g-3">
            @forelse($latest as $book)
                <div class="col-6 col-sm-4 col-md-2 col-lg-4 col-xl-2">@include('partials.book-card', ['book' => $book])</div>
            @empty
                <p class="text-muted">No books in the catalogue yet.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card overflow-hidden">
            <div class="p-3 text-white" style="background: linear-gradient(135deg,#f59e0b,#db2777)">
                <h5 class="text-white mb-1"><i class="bi bi-lightbulb-fill"></i> Most wanted books</h5>
                <div class="small opacity-75">Books students want the library to buy</div>
            </div>
            <ul class="list-group list-group-flush">
                @forelse($topSuggestions as $s)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2 py-3">
                        <span><span class="fw-medium">{{ $s->title }}</span><br><small class="text-muted">{{ $s->author }}</small></span>
                        <span class="badge soft-indigo fs-6">{{ $s->votes_count }} <i class="bi bi-hand-thumbs-up-fill"></i></span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No recommendations yet.</li>
                @endforelse
            </ul>
            <div class="card-body">
                <p class="small text-muted mb-2">Can't find a book, or it's never available? Tell the library — others can support your request.</p>
                <a href="{{ route('suggestions.create') }}" class="btn btn-warning w-100"><i class="bi bi-plus-circle"></i> Recommend a book</a>
                <a href="{{ route('suggestions.index') }}" class="btn btn-link w-100 mt-1">See all recommendations</a>
            </div>
        </div>
    </div>
</div>
@endsection
