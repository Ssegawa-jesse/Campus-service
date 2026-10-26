@extends('layouts.app')
@section('title', 'Books')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Books</h3>
    <a href="{{ route('admin.books.create') }}" class="btn btn-lib"><i class="bi bi-plus-lg"></i> Add book</a>
</div>
<form class="mb-3"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search title, author, ISBN…"></form>
<div class="card shadow-sm">
    <table class="table table-hover mb-0">
        <thead><tr><th>Title</th><th>Author(s)</th><th>ISBN</th><th>Category</th><th>Copies</th><th></th></tr></thead>
        <tbody>
        @forelse($books as $book)
            <tr>
                <td><a href="{{ route('catalog.show', $book) }}">{{ $book->title }}</a></td>
                <td>{{ $book->authorNames() }}</td><td>{{ $book->isbn }}</td><td>{{ $book->category?->name }}</td>
                <td>{{ $book->available_copies_count }} / {{ $book->copies_count }}</td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('admin.copies.index', $book) }}" class="btn btn-sm btn-outline-primary" title="Copies & QR labels"><i class="bi bi-qr-code"></i></a>
                    <a href="{{ route('admin.books.edit', $book) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="{{ route('admin.books.destroy', $book) }}" class="d-inline" onsubmit="return confirm('Delete this book and all its copies?')">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted text-center py-4">No books yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $books->links('pagination::bootstrap-5') }}</div>
@endsection
