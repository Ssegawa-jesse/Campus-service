@extends('layouts.app')
@section('title', $book->title)
@section('content')
<nav class="small mb-3"><a href="{{ route('catalog.index') }}"><i class="bi bi-arrow-left"></i> Back to catalogue</a></nav>
<div class="card p-3 p-md-4 mb-4">
    <div class="row g-4">
        <div class="col-sm-5 col-md-4 col-lg-3">
            <div class="book-tile"><div class="cover-wrap">@include('partials.cover', ['book' => $book])</div></div>
            @auth @if(auth()->user()->isStaff())
                <a href="{{ route('admin.books.edit', $book) }}" class="btn btn-outline-primary btn-sm w-100 mt-3"><i class="bi bi-pencil"></i> Edit book</a>
            @endif @endauth
        </div>
        <div class="col-sm-7 col-md-8 col-lg-9">
            @if($book->category)<a href="{{ route('catalog.index', ['category' => $book->category_id]) }}" class="chip mb-2">{{ $book->category->name }}</a>@endif
            <h2 class="mb-1">{{ $book->title }}</h2>
            <p class="fs-5 text-muted">{{ $book->authorNames() ?: 'Unknown author' }}</p>

            @php($available = $book->copies->where('status', 'available')->count())
            <div class="p-3 rounded-4 mb-3 {{ $available ? 'soft-teal' : ($book->copies->count() ? 'soft-amber' : 'soft-rose') }}">
                @if($available > 0)
                    <h5 class="mb-1" style="color:inherit"><i class="bi bi-check-circle-fill"></i> {{ $available }} of {{ $book->copies->count() }} copies on the shelf</h5>
                    <div class="small">Find it at <strong>{{ $book->shelfLocations() ?: 'the library counter' }}</strong> and borrow it at the counter with your library card.</div>
                @elseif($book->copies->count() > 0)
                    <h5 class="mb-1" style="color:inherit"><i class="bi bi-hourglass-split"></i> All copies are on loan</h5>
                    <div class="small mb-2">@if($nextDue) Next copy due back <strong>{{ $nextDue->format('d M Y') }}</strong>. @endif {{ $waiting }} {{ Str::plural('person', $waiting) }} waiting.</div>
                    @auth
                        @if($myReservation)
                            <div class="fw-semibold"><i class="bi bi-bookmark-check-fill"></i>
                                {{ $myReservation->status === 'ready' ? 'A copy is held for you until '.$myReservation->expires_at->format('d M Y').'.' : 'You are number '.$myReservation->position().' in the waiting list.' }}</div>
                        @elseif(auth()->user()->member)
                            <form method="POST" action="{{ route('my.reservations.store', $book) }}">@csrf
                                <button class="btn btn-primary"><i class="bi bi-bookmark-plus"></i> Reserve — SMS me when it's back</button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Log in to reserve</a>
                    @endauth
                @else
                    <h5 class="mb-1" style="color:inherit"><i class="bi bi-x-circle-fill"></i> The library has no copies of this title yet</h5>
                @endif
                @if($available === 0)
                    <div class="mt-3 pt-3 border-top border-dark border-opacity-10 small">
                        Need it urgently? Recommend that the library buys more copies.
                        <a href="{{ route('suggestions.create', ['book' => $book->id]) }}" class="btn btn-warning btn-sm ms-2"><i class="bi bi-lightbulb"></i> Recommend more copies</a>
                    </div>
                @endif
            </div>

            <div class="row row-cols-2 row-cols-md-4 g-2 mb-3">
                @foreach(['ISBN' => $book->isbn, 'Publisher' => $book->publisher, 'Year' => $book->year, 'Edition' => $book->edition, 'Subject' => $book->subject, 'Shelf' => $book->shelfLocations()] as $label => $value)
                    @if($value)<div class="col"><div class="p-2 rounded-3 bg-light h-100"><div class="small text-muted">{{ $label }}</div><div class="fw-medium">{{ $value }}</div></div></div>@endif
                @endforeach
            </div>
            @if($book->description)<p class="mb-0">{{ $book->description }}</p>@endif
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-stack text-primary"></i> Copies</div>
            <table class="table">
                <thead><tr><th>Copy</th><th>Shelf</th><th>Status</th><th>Due back</th></tr></thead>
                <tbody>
                @foreach($book->copies as $copy)
                    <tr><td><code>{{ $copy->code }}</code></td><td>{{ $copy->shelf_location ?? '—' }}</td>
                        <td>@include('partials.copy-status', ['status' => $copy->status])</td><td>{{ $copy->currentLoan?->due_at->format('d M Y') ?? '—' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($book->suggestions->isNotEmpty())
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-lightbulb text-warning"></i> Recommendations about this title</div>
                <ul class="list-group list-group-flush">
                    @foreach($book->suggestions as $s)
                        <li class="list-group-item small d-flex justify-content-between gap-2">
                            <span>{{ $s->reason ?: 'More copies needed.' }}</span>
                            <span class="text-nowrap"><span class="badge bg-{{ $s->statusColour() }}">{{ $s->statusLabel() }}</span> <span class="badge soft-indigo">{{ $s->votes_count }} <i class="bi bi-hand-thumbs-up-fill"></i></span></span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection
