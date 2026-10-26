<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookSuggestion;
use App\Models\Copy;
use App\Models\DigitalResource;
use App\Models\Member;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'stats' => [
                'titles' => Book::count(),
                'copies' => Copy::count(),
                'available' => Copy::where('status', 'available')->count(),
                'members' => Member::count(),
                'resources' => DigitalResource::count(),
            ],
            'latest' => Book::with('authors')->withCount(['copies', 'availableCopies'])->latest()->take(6)->get(),
            'topSuggestions' => BookSuggestion::whereIn('status', ['pending', 'approved', 'ordered'])
                ->orderByDesc('votes_count')->latest()->take(5)->get(),
        ]);
    }
}
