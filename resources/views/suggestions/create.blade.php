@extends('layouts.app')
@section('title', 'Recommend a book')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4><i class="bi bi-lightbulb text-warning"></i> Recommend a book</h4>
                <p class="text-muted small">Tell the library about a book it doesn't have, or one that is always on loan. Your recommendation is public so others can support it.</p>
                @if($book)
                    <div class="alert alert-info small">You are recommending more copies of <strong>{{ $book->title }}</strong>, which is in the catalogue but not available.</div>
                @endif
                <form method="POST" action="{{ route('suggestions.store') }}">@csrf
                    <input type="hidden" name="book_id" value="{{ old('book_id', $book?->id) }}">
                    <div class="mb-3">
                        <label class="form-label">Book title *</label>
                        <input name="title" value="{{ old('title', $prefill['title']) }}" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label">Author(s)</label>
                            <input name="author" value="{{ old('author', $prefill['author']) }}" class="form-control">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">ISBN</label>
                            <input name="isbn" value="{{ old('isbn', $prefill['isbn']) }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course / course unit it is needed for</label>
                        <input name="course" value="{{ old('course') }}" class="form-control" placeholder="e.g. BBC 2101 Database Systems">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Why should the library get it?</label>
                        <textarea name="reason" rows="4" class="form-control" placeholder="e.g. It is on our reading list and the only copy is always borrowed.">{{ old('reason') }}</textarea>
                    </div>
                    @auth
                        <button class="btn btn-warning w-100"><i class="bi bi-send"></i> Submit recommendation</button>
                    @endauth
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
