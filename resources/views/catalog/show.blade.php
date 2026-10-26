@extends('layouts.app')
@section('title', $book->title)
@section('content')
<nav class="small mb-3"><a href="{{ route('catalog.index') }}">&larr; Back to catalogue</a></nav>
<div class="row g-4">
    <div class="col-md-3">
        @if($book->coverUrl())<img src="{{ $book->coverUrl() }}" class="book-cover shadow-sm" alt="">
        @else<div class="book-cover shadow-sm"><i class="bi bi-book"></i></div>@endif
    </div>
    <div class="col-md-9">
        <h2>{{ $book->title }}</h2>
        <p class="lead text-muted">{{ $book->authorNames() ?: 'Unknown author' }}</p>
        <dl class="row small">
            @foreach(['ISBN' => $book->isbn, 'Publisher' => $book->publisher, 'Year' => $book->year, 'Edition' => $book->edition, 'Category' => $book->category?->name, 'Subject' => $book->subject, 'Shelf location' => $book->shelfLocations()] as $label => $value)
                @if($value)<dt class="col-sm-3">{{ $label }}</dt><dd class="col-sm-9">{{ $value }}</dd>@endif
            @endforeach
        </dl>
        @if($book->description)<p>{{ $book->description }}</p>@endif

        @php($available = $book->copies->where('status', 'available')->count())
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                @if($available > 0)
                    <h5 class="text-success mb-1"><i class="bi bi-check-circle"></i> {{ $available }} of {{ $book->copies->count() }} copies on the shelf</h5>
                    <p class="mb-0 small">Go to <strong>{{ $book->shelfLocations() ?: 'the library counter' }}</strong> and borrow it at the counter with your library card.</p>
                @elseif($book->copies->count() > 0)
                    <h5 class="text-warning mb-1"><i class="bi bi-hourglass-split"></i> All copies are currently on loan</h5>
                    <p class="small mb-2">
                        @if($nextDue) Next copy due back {{ $nextDue->format('d M Y') }}. @endif
                        {{ $waiting }} {{ Str::plural('person', $waiting) }} waiting.
                    </p>
                    @auth
                        @if($myReservation)
                            <div class="alert alert-info mb-0">
                                @if($myReservation->status === 'ready')
                                    A copy is being held for you until {{ $myReservation->expires_at->format('d M Y') }}.
                                @else
                                    You are number {{ $myReservation->position() }} in the waiting list.
                                @endif
                            </div>
                        @elseif(auth()->user()->member)
                            <form method="POST" action="{{ route('my.reservations.store', $book) }}" class="d-inline">@csrf
                                <button class="btn btn-lib"><i class="bi bi-bookmark-plus"></i> Reserve — notify me by SMS when available</button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-lib">Log in to reserve</a>
                    @endauth
                @else
                    <h5 class="text-secondary mb-1"><i class="bi bi-x-circle"></i> The library has no copies of this title</h5>
                @endif

                @if($available === 0)
                    <hr>
                    <p class="small mb-2">Not available when you need it? Recommend that the library buys more copies — others can support your recommendation.</p>
                    <a href="{{ route('suggestions.create', ['book' => $book->id]) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-lightbulb"></i> Recommend more copies</a>
                @endif
            </div>
        </div>

        <h6>Copies</h6>
        <table class="table table-sm bg-white">
            <thead><tr><th>Copy</th><th>Shelf</th><th>Status</th><th>Due back</th></tr></thead>
            <tbody>
            @foreach($book->copies as $copy)
                <tr>
                    <td>{{ $copy->code }}</td>
                    <td>{{ $copy->shelf_location ?? '—' }}</td>
                    <td>@include('partials.copy-status', ['status' => $copy->status])</td>
                    <td>{{ $copy->currentLoan?->due_at->format('d M Y') ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        @if($book->suggestions->isNotEmpty())
            <h6 class="mt-4">Recommendations about this title</h6>
            <ul class="list-group">
                @foreach($book->suggestions as $s)
                    <li class="list-group-item small d-flex justify-content-between">
                        <span>{{ $s->reason ?: 'More copies needed.' }}</span>
                        <span><span class="badge bg-{{ $s->statusColour() }}">{{ $s->statusLabel() }}</span> <span class="badge bg-primary">{{ $s->votes_count }} <i class="bi bi-hand-thumbs-up"></i></span></span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
