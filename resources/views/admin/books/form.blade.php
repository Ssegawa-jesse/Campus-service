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
        <div class="col-md-6"><label class="form-label">Cover image</label><input type="file" name="cover" accept="image/*" class="form-control"></div>
        @unless($book->exists)
            <div class="col-md-3"><label class="form-label">Number of copies</label><input type="number" name="copies" value="{{ old('copies', 1) }}" min="0" max="100" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Shelf location</label><input name="shelf_location" value="{{ old('shelf_location') }}" class="form-control" placeholder="e.g. Shelf B3"></div>
        @endunless
    </div>
    <div class="mt-4"><button class="btn btn-lib">Save</button> <a href="{{ route('admin.books.index') }}" class="btn btn-link">Cancel</a></div>
</form>
@endsection
