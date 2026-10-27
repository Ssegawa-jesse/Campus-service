<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::query()
            ->with(['authors', 'category', 'copies'])
            ->withCount(['copies', 'availableCopies'])
            ->search($request->query('q'))
            ->when($request->query('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('availability') === 'available', fn ($q) => $q->has('availableCopies'))
            ->when($request->query('availability') === 'unavailable', fn ($q) => $q->doesntHave('availableCopies'))
            ->orderBy('title')
            ->paginate(18)
            ->withQueryString();

        return view('catalog.index', [
            'books' => $books,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function show(Book $book)
    {
        $book->load(['authors', 'category', 'copies.currentLoan', 'suggestions' => fn ($q) => $q->latest()]);
        $member = auth()->user()?->member;

        return view('catalog.show', [
            'book' => $book,
            'waiting' => $book->waitingReservations()->count(),
            'myReservation' => $member?->reservations()->where('book_id', $book->id)->whereIn('status', ['waiting', 'ready'])->first(),
            'nextDue' => $book->copies->pluck('currentLoan')->filter()->min('due_at'),
        ]);
    }
}
