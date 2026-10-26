<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Book;
use App\Models\BookSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Book recommendations: members recommend books the library does not have (or that are
 * never available), and everyone can see the list and support recommendations they agree with.
 */
class SuggestionController extends Controller
{
    public function index(Request $request)
    {
        $suggestions = BookSuggestion::query()
            ->with(['user', 'book'])
            ->when($request->query('q'), function ($q, $term) {
                $q->where(fn ($q) => $q->where('title', 'like', "%{$term}%")
                    ->orWhere('author', 'like', "%{$term}%")
                    ->orWhere('course', 'like', "%{$term}%"));
            })
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('sort') === 'newest', fn ($q) => $q->latest(), fn ($q) => $q->orderByDesc('votes_count')->latest())
            ->paginate(15)
            ->withQueryString();

        $votedIds = $request->user()
            ? DB::table('suggestion_votes')->where('user_id', $request->user()->id)->pluck('book_suggestion_id')->all()
            : [];

        return view('suggestions.index', compact('suggestions', 'votedIds'));
    }

    public function create(Request $request)
    {
        $book = $request->query('book') ? Book::with('authors')->find($request->query('book')) : null;

        return view('suggestions.create', [
            'book' => $book,
            'prefill' => [
                'title' => $book?->title ?? $request->query('title'),
                'author' => $book?->authorNames(),
                'isbn' => $book?->isbn,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'course' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'book_id' => ['nullable', 'exists:books,id'],
        ]);

        // If someone already recommended the same title and it is still open, add a vote instead of a duplicate.
        $existing = BookSuggestion::whereRaw('lower(title) = ?', [mb_strtolower(trim($data['title']))])
            ->whereIn('status', ['pending', 'approved', 'ordered'])
            ->first();

        if ($existing) {
            $this->addVote($existing, $request->user()->id);

            return redirect()->route('suggestions.index')
                ->with('success', "\"{$existing->title}\" was already recommended — your support has been added to it.");
        }

        $suggestion = $request->user()->suggestions()->create($data + ['status' => 'pending']);
        $this->addVote($suggestion, $request->user()->id);
        AuditLog::record('suggestion.created', "Recommended \"{$suggestion->title}\"");

        return redirect()->route('suggestions.index')->with('success', 'Thank you! Your recommendation has been sent to the library.');
    }

    public function vote(Request $request, BookSuggestion $suggestion)
    {
        $userId = $request->user()->id;
        $voted = DB::table('suggestion_votes')->where(['book_suggestion_id' => $suggestion->id, 'user_id' => $userId])->exists();

        if ($voted) {
            DB::table('suggestion_votes')->where(['book_suggestion_id' => $suggestion->id, 'user_id' => $userId])->delete();
            $suggestion->decrement('votes_count');
        } else {
            $this->addVote($suggestion, $userId);
        }

        return back();
    }

    public function destroy(Request $request, BookSuggestion $suggestion)
    {
        abort_unless($suggestion->user_id === $request->user()->id || $request->user()->isStaff(), 403);
        abort_if($suggestion->status !== 'pending' && ! $request->user()->isStaff(), 403, 'Only pending recommendations can be withdrawn.');

        $suggestion->delete();

        return redirect()->route('suggestions.index')->with('success', 'Recommendation removed.');
    }

    protected function addVote(BookSuggestion $suggestion, int $userId): void
    {
        $inserted = DB::table('suggestion_votes')->insertOrIgnore([
            'book_suggestion_id' => $suggestion->id,
            'user_id' => $userId,
            'created_at' => now(),
        ]);

        if ($inserted) {
            $suggestion->increment('votes_count');
        }
    }
}
