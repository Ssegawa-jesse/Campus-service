@extends('layouts.app')
@section('title', $book->exists ? 'Edit book' : 'Add book')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">{{ $book->exists ? 'Edit book' : 'Add book' }}</h3>
<form method="POST" action="{{ $book->exists ? route('admin.books.update', $book) : route('admin.books.store') }}" enctype="multipart/form-data" class="card card-body shadow-sm">
    @csrf @if($book->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Title *</label><input name="title" value="{{ old('title', $book->title) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">ISBN</label><input name="isbn" value="{{ old('isbn', $book->isbn) }}" class="form-control"></div>
        <div class="col-md-8"><label class="form-label">Author(s)</label><input name="authors" value="{{ old('authors', $book->exists ? $book->authorNames() : '') }}" class="form-control" placeholder="Separate several authors with commas"></div>
        <div class="col-md-4">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
                <option value="">—</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $book->category_id) == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">Publisher</label><input name="publisher" value="{{ old('publisher', $book->publisher) }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Year</label><input type="number" name="year" value="{{ old('year', $book->year) }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Edition</label><input name="edition" value="{{ old('edition', $book->edition) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Subject / keywords</label><input name="subject" value="{{ old('subject', $book->subject) }}" class="form-control"></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control">{{ old('description', $book->description) }}</textarea></div>
        <div class="col-12">
            <div class="d-flex gap-3 align-items-start p-3 rounded-4 bg-light">
                <div style="width:90px" class="book-tile"><div class="cover-wrap" id="cover-preview">@if($book->exists) @include('partials.cover', ['book' => $book]) @else <div class="gen-cover" style="background:linear-gradient(160deg,#a5b4fc,#6366f1)"><div class="gc-title">Cover</div></div> @endif</div></div>
                <div class="flex-grow-1">
                    <label class="form-label">Cover image</label>
                    <input type="file" name="cover" accept="image/*" class="form-control" id="cover-input">
                    <div class="form-text">Upload a photo of the cover (JPG/PNG, max 2 MB). Without one, a colourful cover is generated automatically.</div>
                    @unless($book->exists)
                        <div class="form-check mt-2"><input type="checkbox" name="fetch_cover" value="1" id="fetch_cover" class="form-check-input" checked>
                            <label for="fetch_cover" class="form-check-label small">If no image is uploaded, download the real cover from the internet using the ISBN (Open Library)</label></div>
                    @endunless
                </div>
            </div>
        </div>
        @unless($book->exists)
            <div class="col-md-3"><label class="form-label">Number of copies</label><input type="number" name="copies" value="{{ old('copies', 1) }}" min="0" max="100" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Shelf location</label><input name="shelf_location" value="{{ old('shelf_location') }}" class="form-control" placeholder="e.g. Shelf B3"></div>
        @endunless
    </div>
    <div class="mt-4 d-flex flex-wrap gap-2 align-items-center"><button class="btn btn-primary px-4">Save</button> <a href="{{ route('admin.books.index') }}" class="btn btn-link">Cancel</a>
        @if($book->exists && $book->isbn)<button form="fetch-cover" class="btn btn-outline-primary ms-auto"><i class="bi bi-cloud-download"></i> Get real cover online (ISBN)</button>@endif
    </div>
</form>
@if($book->exists)<form method="POST" action="{{ route('admin.books.fetch-cover', $book) }}" id="fetch-cover">@csrf</form>@endif
@push('scripts')
<script>
document.getElementById('cover-input')?.addEventListener('change', e => {
    const f = e.target.files[0]; if (!f) return;
    const img = document.createElement('img'); img.className = 'book-cover'; img.src = URL.createObjectURL(f);
    document.getElementById('cover-preview').replaceChildren(img);
});
</script>
@endpush
@endsection
