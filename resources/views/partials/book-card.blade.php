<div class="card h-100 shadow-sm">
    <div class="row g-0 h-100">
        <div class="col-4 p-2">
            @if($book->coverUrl())
                <img src="{{ $book->coverUrl() }}" class="book-cover" alt="">
            @else
                <div class="book-cover"><i class="bi bi-book"></i></div>
            @endif
        </div>
        <div class="col-8">
            <div class="card-body p-2 pe-3 d-flex flex-column h-100">
                <h6 class="mb-1"><a href="{{ route('catalog.show', $book) }}" class="text-decoration-none stretched-link">{{ $book->title }}</a></h6>
                <div class="small text-muted mb-2">{{ $book->authorNames() ?: 'Unknown author' }}@if($book->year) · {{ $book->year }}@endif</div>
                <div class="mt-auto small">
                    @if($book->available_copies_count > 0)
                        <span class="badge bg-success">{{ $book->available_copies_count }} of {{ $book->copies_count }} available</span>
                    @elseif($book->copies_count > 0)
                        <span class="badge bg-warning text-dark">All {{ $book->copies_count }} on loan</span>
                    @else
                        <span class="badge bg-secondary">No copies yet</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
