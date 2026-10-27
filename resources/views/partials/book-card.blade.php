<a href="{{ route('catalog.show', $book) }}" class="book-tile">
    <div class="cover-wrap">
        @include('partials.cover', ['book' => $book])
        @if($book->available_copies_count > 0)
            <span class="badge bg-success avail"><i class="bi bi-check-circle"></i> {{ $book->available_copies_count }} available</span>
        @elseif($book->copies_count > 0)
            <span class="badge bg-warning text-dark avail"><i class="bi bi-hourglass-split"></i> On loan</span>
        @else
            <span class="badge bg-secondary avail">No copies</span>
        @endif
    </div>
    <div class="title">{{ $book->title }}</div>
    <div class="author">{{ $book->authorNames() ?: 'Unknown author' }}</div>
</a>
