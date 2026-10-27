<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookSuggestion;
use App\Models\Category;
use App\Models\Copy;
use App\Models\DigitalResource;
use App\Models\Member;

class HomeController extends Controller
{
    public function __invoke()
    {
        $withCounts = fn ($q) => $q->with('authors')->withCount(['copies', 'availableCopies']);

        return view('home', [
            'stats' => [
                'titles' => Book::count(),
                'copies' => Copy::count(),
                'available' => Copy::where('status', 'available')->count(),
                'members' => Member::count(),
                'resources' => DigitalResource::count(),
            ],
            'heroBooks' => Book::whereNotNull('cover_image')->inRandomOrder()->take(3)->get(),
            'latest' => $withCounts(Book::query())->latest('id')->take(6)->get(),
            'popular' => $withCounts(Book::query())->withCount('loans')->orderByDesc('loans_count')->take(6)->get(),
            'categories' => Category::has('books')->withCount('books')->orderByDesc('books_count')->get(),
            'topSuggestions' => BookSuggestion::whereIn('status', ['pending', 'approved', 'ordered'])
                ->orderByDesc('votes_count')->latest()->take(5)->get(),
        ]);
    }
}
