<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Copy;
use App\Services\CoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.books.index', [
            'books' => Book::with('authors', 'category')
                ->withCount(['copies', 'availableCopies'])
                ->search($request->query('q'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.books.form', ['book' => new Book, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $request->validate([
            'copies' => ['nullable', 'integer', 'min:0', 'max:100'],
            'shelf_location' => ['nullable', 'string', 'max:100'],
        ]);

        $book = DB::transaction(function () use ($request, $data) {
            $book = Book::create($data);
            $this->syncAuthors($book, $request->input('authors'));

            for ($i = 0; $i < (int) $request->input('copies', 0); $i++) {
                $book->copies()->create([
                    'code' => Copy::generateCode(),
                    'shelf_location' => $request->input('shelf_location'),
                    'condition' => 'new',
                ]);
            }

            return $book;
        });

        if (! $book->cover_image && $request->boolean('fetch_cover')) {
            app(CoverService::class)->fetch($book);
        }

        AuditLog::record('book.created', "Added \"{$book->title}\" with {$book->copies()->count()} copies");

        return redirect()->route('admin.copies.index', $book)->with('success', 'Book added to the catalogue.');
    }

    public function edit(Book $book)
    {
        return view('admin.books.form', ['book' => $book->load('authors'), 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Book $book)
    {
        $book->update($this->validated($request, $book));
        $this->syncAuthors($book, $request->input('authors'));
        AuditLog::record('book.updated', "Updated \"{$book->title}\"");

        return redirect()->route('admin.books.index')->with('success', 'Book updated.');
    }

    public function fetchCover(Book $book, CoverService $covers)
    {
        if (! $book->isbn) {
            return back()->withErrors(['book' => 'Add an ISBN to this book first.']);
        }

        return $covers->fetch($book)
            ? back()->with('success', 'Cover downloaded from Open Library.')
            : back()->withErrors(['book' => 'No cover found online for this ISBN (or no internet connection). You can upload an image instead.']);
    }

    public function destroy(Book $book)
    {
        if ($book->copies()->whereIn('status', ['on_loan', 'on_hold'])->exists()) {
            return back()->withErrors(['book' => 'This title has copies on loan or on hold and cannot be deleted.']);
        }

        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }
        AuditLog::record('book.deleted', "Deleted \"{$book->title}\"");
        $book->delete();

        return redirect()->route('admin.books.index')->with('success', 'Book deleted.');
    }

    protected function validated(Request $request, ?Book $book = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['nullable', 'string', 'max:500'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 1)],
            'edition' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'cover' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('cover')) {
            if ($book?->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $data['cover_image'] = $request->file('cover')->store('covers', 'public');
        }

        unset($data['authors'], $data['cover']);

        return $data;
    }

    protected function syncAuthors(Book $book, ?string $names): void
    {
        $ids = collect(explode(',', (string) $names))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->map(fn ($name) => Author::firstOrCreate(['name' => $name])->id);

        $book->authors()->sync($ids);
    }
}
